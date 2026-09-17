/**
 * SYCS DM Module
 */

import { api } from './api.js';
import { showToast } from './ui.js';
import { renderMessageNode } from './message.js';
import { t } from './utils.js';

let currentDmPartnerId = null;

/**
 * Switch to a DM chat with a specific user
 * @param {number} userId
 * @param {string} userName
 */
export async function switchToDm(userId, userName) {
  currentDmPartnerId = userId;
  window.currentDmPartnerId = userId;
  document.getElementById("dm-hub-view").style.display = "none";
  document.getElementById("dm-chat-view").style.display = "flex";
  document.getElementById("current-header-title").innerText = userName;

  await loadDmMessages(userId);
}

/**
 * Load DM messages with a specific partner using the dedicated DM API
 * @param {number} partnerId
 */
export async function loadDmMessages(partnerId) {
  const container = document.getElementById("dm-message-container");
  if (!container) return;

  container.innerText = "";
  const messages = await api(`get_direct_messages&partner_id=${partnerId}`);

  if (!messages || messages.error) {
    console.error("Failed to load DM messages:", messages?.error);
    return;
  }

  if (!Array.isArray(messages) || messages.length === 0) {
    const div = document.createElement('div');
    div.className = 'empty-state';
    const p = document.createElement('p');
    p.textContent = t('no_messages', 'ｼｰﾝ...静かな場所ですね。');
    div.appendChild(p);
    container.appendChild(div);
  } else {
    const context = {
      currentUserName: window.SYCS_CONFIG.currentUserName,
      currentUserId: window.SYCS_CONFIG.currentUserId
    };
    messages.forEach(msg => renderMessageNode(msg, container, context, {}));
  }

  container.scrollTop = container.scrollHeight;
}

/**
 * If a DM with the given senderId is currently open, refresh the messages.
 * Used by the SSE onNewDm callback to enable instant in-view updates.
 * @param {object} data - SSE event data (senderId, content, etc.)
 */
export function refreshDmIfOpen(data) {
  const senderId = data.senderId ?? data.sender_id;
  if (currentDmPartnerId && currentDmPartnerId == senderId) {
    loadDmMessages(currentDmPartnerId);
  }
}

/**
 * Go back to DM hub (friend list)
 */
export function backToHub() {
  currentDmPartnerId = null;
  window.currentDmPartnerId = null;
  document.getElementById("dm-hub-view").style.display = "flex";
  document.getElementById("dm-chat-view").style.display = "none";
}

/**
 * Send a DM message
 * Uses the dedicated DM API: action=send_direct_message, param=receiver_id
 */
export async function sendDm() {
  const input = document.getElementById("dm-msg-input");
  const content = input.value.trim();
  if (!content || !currentDmPartnerId) return;

  const res = await api("send_direct_message", "POST", {
    receiver_id: currentDmPartnerId,
    content: content
  });

  if (res && res.success) {
    input.value = "";
    // Reload DM messages to show the newly sent message
    await loadDmMessages(currentDmPartnerId);
  }
}

/**
 * Block current DM partner
 * Uses target_id to match FriendHandler::blockUser()
 */
export async function blockCurrentPartner() {
  if (currentDmPartnerId && confirm(t("block_confirm", "このユーザーをブロックしますか？"))) {
    const res = await api("block_user", "POST", { target_id: currentDmPartnerId });
    if (res && res.success) {
      showToast(t("success", "成功"), t("blocked", "ブロックしました"), "success");
      backToHub();
    }
  }
}

/**
 * Handle DM input key events
 * @param {KeyboardEvent} event
 */
export function handleDmInputKey(event) {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    sendDm();
  }
}

/**
 * Handle Typing Indicator
 */
export function handleTyping() {
  // Logic to emit typing event via Socket.io
  console.log("User is typing...");
}

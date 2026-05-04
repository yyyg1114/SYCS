/**
 * SYCS DM Module
 */

import { api } from './api.js';
import { showToast, switchTab } from './ui.js';
import { loadMessages } from './chat.js';
import { t } from './utils.js';

let currentDmPartnerId = null;

/**
 * Switch to a DM chat with a specific user
 * @param {number} userId 
 * @param {string} userName 
 */
export async function switchToDm(userId, userName) {
  currentDmPartnerId = userId;
  document.getElementById("dm-hub-view").style.display = "none";
  document.getElementById("dm-chat-view").style.display = "flex";
  document.getElementById("current-header-title").innerText = userName;
  
  const container = document.getElementById("dm-message-container");
  const context = {
    currentUserName: window.SYCS_CONFIG.currentUserName,
    currentUserId: window.SYCS_CONFIG.currentUserId
  };
  
  await loadMessages(`dm_${userId}`, container, context, {});
}

/**
 * Go back to DM hub (friend list)
 */
export function backToHub() {
  currentDmPartnerId = null;
  document.getElementById("dm-hub-view").style.display = "flex";
  document.getElementById("dm-chat-view").style.display = "none";
}

/**
 * Send a DM message
 */
export async function sendDm() {
  const input = document.getElementById("dm-msg-input");
  const content = input.value.trim();
  if (!content || !currentDmPartnerId) return;

  const res = await api("send_dm", "POST", {
    recipient_id: currentDmPartnerId,
    content: content
  });

  if (res && res.success) {
    input.value = "";
    switchToDm(currentDmPartnerId, document.getElementById("current-header-title").innerText);
  }
}

/**
 * Block current DM partner
 */
export async function blockCurrentPartner() {
  if (currentDmPartnerId && confirm(t("block_confirm", "このユーザーをブロックしますか？"))) {
    const res = await api("block_user", "POST", { block_id: currentDmPartnerId });
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

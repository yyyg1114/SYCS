/**
 * SYCS Friend Management Module
 */

import { api } from './api.js';
import { showToast, showModal, closeModal } from './ui.js';
import { t } from './utils.js';

/**
 * Show Add Friend Modal
 */
export function showAddFriendModal() {
  showModal("add-friend-modal");
}

/**
 * Search users to add as friends
 */
export async function searchUsers() {
  const query = document.getElementById("user-search-input").value.trim();
  if (!query) return;

  const results = await api(`search_users&q=${encodeURIComponent(query)}`);
  const container = document.getElementById("user-search-results");
  if (!container) return;

  container.innerHTML = "";
  if (results.length === 0) {
    container.innerHTML = `<div class="empty-state">${t("no_users_found", "ユーザーが見つかりませんでした")}</div>`;
  } else {
    results.forEach(user => {
      const div = document.createElement("div");
      div.className = "user-search-item";
      div.innerHTML = `
        <div class="user-meta">
          <span class="user-name">${user.username}</span>
        </div>
        <button class="btn-primary mini" onclick="sendFriendRequest(${user.id})">${t("add_friend", "フレンド申請")}</button>
      `;
      container.appendChild(div);
    });
  }
}

/**
 * Send friend request
 * @param {number} userId 
 */
export async function sendFriendRequest(userId) {
  const res = await api("send_friend_request", "POST", { friend_id: userId });
  if (res && res.success) {
    showToast(t("success", "成功"), t("request_sent", "フレンド申請を送信しました"), "success");
    closeModal("add-friend-modal");
  } else {
    showToast(t("error", "エラー"), res.error || t("failed_to_send", "送信に失敗しました"), "error");
  }
}

/**
 * Show Pending Requests Modal
 */
export async function showPendingRequestsModal() {
  showModal("pending-requests-modal");
  const list = document.getElementById("pending-requests-list-modal");
  if (!list) return;

  list.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
  const res = await api("get_pending_requests");
  list.innerHTML = "";
  if (res && res.length > 0) {
    res.forEach(req => {
      const div = document.createElement("div");
      div.className = "request-item";
      div.innerHTML = `
        <span>${req.username}</span>
        <div class="actions">
          <button class="btn-primary mini" onclick="handleFriendRequest(${req.id}, 'accept')">${t("accept", "承認")}</button>
          <button class="btn-secondary mini" onclick="handleFriendRequest(${req.id}, 'reject')">${t("reject", "拒否")}</button>
        </div>
      `;
      list.appendChild(div);
    });
  } else {
    list.innerHTML = `<div class="empty-state">${t("no_pending", "待機中の申請はありません")}</div>`;
  }
}

/**
 * Handle friend request (accept/reject)
 * @param {number} requestId 
 * @param {string} action 
 */
export async function handleFriendRequest(requestId, action) {
  const res = await api("handle_friend_request", "POST", { request_id: requestId, action });
  if (res && res.success) {
    showToast(t("success", "成功"), action === 'accept' ? t("accepted", "承認しました") : t("rejected", "拒否しました"), "success");
    showPendingRequestsModal(); // Refresh
  }
}

/**
 * Show Blocked Users Modal
 */
export async function showBlockedModal() {
  showModal("blocked-users-modal");
  const list = document.getElementById("blocked-users-list");
  if (!list) return;

  list.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
  const res = await api("get_blocked_users");
  list.innerHTML = "";
  if (res && res.length > 0) {
    res.forEach(user => {
      const div = document.createElement("div");
      div.className = "blocked-item";
      div.innerHTML = `
        <span>${user.username}</span>
        <button class="btn-secondary mini" onclick="unblockUser(${user.id})">${t("unblock", "ブロック解除")}</button>
      `;
      list.appendChild(div);
    });
  } else {
    list.innerHTML = `<div class="empty-state">${t("no_blocked", "ブロック中のユーザーはいません")}</div>`;
  }
}

/**
 * Block a user
 * @param {number} userId 
 */
export async function blockUser(userId) {
  const res = await api("block_user", "POST", { block_id: userId });
  if (res && res.success) {
    showToast(t("success", "成功"), t("blocked", "ブロックしました"), "success");
  }
}

/**
 * Unblock a user
 * @param {number} userId 
 */
export async function unblockUser(userId) {
  const res = await api("unblock_user", "POST", { block_id: userId });
  if (res && res.success) {
    showToast(t("success", "成功"), t("unblocked", "ブロック解除しました"), "success");
    showBlockedModal(); // Refresh
  }
}

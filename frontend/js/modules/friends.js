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
    loadFriends(); // Refresh friend list in hub
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
    loadFriends();
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
    loadFriends();
  }
}

/**
 * Load accepted friends and render to the hub list
 */
export async function loadFriends() {
  const container = document.getElementById("hub-friend-list");
  if (!container) return;

  container.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
  const friends = await api("get_friends");
  container.innerHTML = "";

  if (friends && friends.length > 0) {
    friends.forEach(friend => {
      const item = document.createElement("div");
      item.className = "thread-item friend-item";
      item.style.cssText = "display: flex; align-items: center; justify-content: space-between; padding: 10px; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 0.2s;";
      
      const avatarUrl = friend.avatar_url ? friend.avatar_url : "assets/img/default_avatar.svg";
      
      item.innerHTML = `
        <div class="user-meta" style="display:flex; align-items:center; gap:10px; flex:1;">
          <img src="${avatarUrl}" class="avatar-mini" style="width:32px; height:32px; border-radius:50%; object-fit:cover;" onError="this.src='assets/img/default_avatar.svg'">
          <div style="flex:1;">
            <div class="user-name" style="font-weight:600; color:var(--text-primary);">${friend.username}</div>
            <div class="user-status" style="font-size:0.75rem; color:var(--text-secondary);">${friend.custom_status || ''}</div>
          </div>
        </div>
        <span class="status-indicator status-${friend.status || 'offline'}" style="width:8px; height:8px; border-radius:50%; display:inline-block;"></span>
      `;
      item.onclick = () => {
        window.switchToDm(friend.id, friend.username);
      };
      container.appendChild(item);
    });
  } else {
    container.innerHTML = `<div class="empty-state">${t("no_friends", "フレンドがいません")}</div>`;
  }
}

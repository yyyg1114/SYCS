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

  container.textContent = '';
  if (!results || results.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.textContent = t('no_users_found', 'ユーザーが見つかりませんでした');
    container.appendChild(empty);
  } else {
    results.forEach(user => {
      const div = document.createElement('div');
      div.className = 'user-search-item';
      const meta = document.createElement('div');
      meta.className = 'user-meta';
      const nameSpan = document.createElement('span');
      nameSpan.className = 'user-name';
      nameSpan.textContent = user.username;
      meta.appendChild(nameSpan);
      const btn = document.createElement('button');
      btn.className = 'btn-primary mini';
      btn.textContent = t('add_friend', 'フレンド申請');
      btn.addEventListener('click', () => sendFriendRequest(user.id));
      div.appendChild(meta);
      div.appendChild(btn);
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

  list.textContent = '';
  const loading = document.createElement('div');
  loading.className = 'loading';
  loading.textContent = t('loading', '読み込み中...');
  list.appendChild(loading);
  const res = await api('get_pending_requests');
  list.textContent = '';
  if (res && res.length > 0) {
    res.forEach(req => {
      const div = document.createElement('div');
      div.className = 'request-item';
      const span = document.createElement('span');
      span.textContent = req.username;
      const actions = document.createElement('div');
      actions.className = 'actions';
      const acceptBtn = document.createElement('button');
      acceptBtn.className = 'btn-primary mini';
      acceptBtn.textContent = t('accept', '承認');
      acceptBtn.addEventListener('click', () => handleFriendRequest(req.id, 'accept'));
      const rejectBtn = document.createElement('button');
      rejectBtn.className = 'btn-secondary mini';
      rejectBtn.textContent = t('reject', '拒否');
      rejectBtn.addEventListener('click', () => handleFriendRequest(req.id, 'reject'));
      actions.appendChild(acceptBtn);
      actions.appendChild(rejectBtn);
      div.appendChild(span);
      div.appendChild(actions);
      list.appendChild(div);
    });
  } else {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.textContent = t('no_pending', '待機中の申請はありません');
    list.appendChild(empty);
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

  list.textContent = '';
  const loadingBlocked = document.createElement('div');
  loadingBlocked.className = 'loading';
  loadingBlocked.textContent = t('loading', '読み込み中...');
  list.appendChild(loadingBlocked);
  const res2 = await api('get_blocked_users');
  list.textContent = '';
  if (res2 && res2.length > 0) {
    res2.forEach(user => {
      const div = document.createElement('div');
      div.className = 'blocked-item';
      const span = document.createElement('span');
      span.textContent = user.username;
      const btn = document.createElement('button');
      btn.className = 'btn-secondary mini';
      btn.textContent = t('unblock', 'ブロック解除');
      btn.addEventListener('click', () => unblockUser(user.id));
      div.appendChild(span);
      div.appendChild(btn);
      list.appendChild(div);
    });
  } else {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.textContent = t('no_blocked', 'ブロック中のユーザーはいません');
    list.appendChild(empty);
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

  container.textContent = '';
  const loadingFriends = document.createElement('div');
  loadingFriends.className = 'loading';
  loadingFriends.textContent = t('loading', '読み込み中...');
  container.appendChild(loadingFriends);
  const friends = await api('get_friends');
  container.textContent = '';

  if (friends && friends.length > 0) {
    friends.forEach(friend => {
      const item = document.createElement('div');
      item.className = 'thread-item friend-item';
      item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 10px; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 0.2s;';
      const avatarUrl = friend.avatar_url ? friend.avatar_url : 'assets/img/default_avatar.svg';
      const userMeta = document.createElement('div');
      userMeta.className = 'user-meta';
      userMeta.style.cssText = 'display:flex; align-items:center; gap:10px; flex:1;';
      const img = document.createElement('img');
      img.src = avatarUrl;
      img.className = 'avatar-mini';
      img.style.cssText = 'width:32px; height:32px; border-radius:50%; object-fit:cover;';
      img.onerror = () => { img.src = 'assets/img/default_avatar.svg'; };
      const metaRight = document.createElement('div');
      metaRight.style.flex = '1';
      const nameDiv = document.createElement('div');
      nameDiv.className = 'user-name';
      nameDiv.style.cssText = 'font-weight:600; color:var(--text-primary);';
      nameDiv.textContent = friend.username;
      const statusDiv = document.createElement('div');
      statusDiv.className = 'user-status';
      statusDiv.style.cssText = 'font-size:0.75rem; color:var(--text-secondary);';
      statusDiv.textContent = friend.custom_status || '';
      metaRight.appendChild(nameDiv);
      metaRight.appendChild(statusDiv);
      userMeta.appendChild(img);
      userMeta.appendChild(metaRight);
      const statusIndicator = document.createElement('span');
      statusIndicator.className = 'status-indicator status-' + (friend.status || 'offline');
      statusIndicator.style.cssText = 'width:8px; height:8px; border-radius:50%; display:inline-block;';
      item.appendChild(userMeta);
      item.appendChild(statusIndicator);
      item.onclick = () => {
        window.switchToDm(friend.id, friend.username);
      };
      container.appendChild(item);
    });
  } else {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.textContent = t('no_friends', 'フレンドがいません');
    container.appendChild(empty);
  }
}

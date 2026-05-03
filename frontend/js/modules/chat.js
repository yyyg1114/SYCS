/**
 * SYCS Chat Module
 */

import { api } from './api.js';
import { renderMessageNode } from './message.js';
import { getSkeletonLoader, t } from './utils.js';
import { showToast, switchTab } from './ui.js';

export async function loadThreads(callback) {
  const threads = await api("get_threads");
  const list = document.getElementById("thread-list");
  if (!list) return;
  list.innerText = "";

  const categories = {};
  threads.forEach((t) => {
    const cat = t.category || "General";
    if (!categories[cat]) categories[cat] = [];
    categories[cat].push(t);
  });

  for (const [catName, catThreads] of Object.entries(categories)) {
    const catHeader = document.createElement("div");
    catHeader.className = "category-header";
    catHeader.innerText = catName;
    list.appendChild(catHeader);

    catThreads.forEach((th) => {
      const item = document.createElement("div");
      item.className = "thread-item";
      item.textContent = "# " + th.name;
      item.onclick = () => callback(th);
      list.appendChild(item);
    });
  }
}

export async function loadGroupThreads(callback) {
  const groups = await api("get_group_threads");
  const list = document.getElementById("group-list");
  if (!list) return;
  list.innerText = "";
  groups.forEach((g) => {
    const item = document.createElement("div");
    item.className = "thread-item";
    item.textContent = "👥 " + g.name;
    item.onclick = () => callback(g);
    list.appendChild(item);
  });
}

export async function loadMessages(threadId, container, context, callbacks) {
  const messages = await api(`get_messages&thread_id=${threadId}`);
  container.innerText = "";
  
  if (messages.error) {
    console.error("Failed to load messages:", messages.error);
    return;
  }

  if (messages.length === 0) {
    const div = document.createElement("div");
    div.className = "empty-state";
    div.innerHTML = `<p>${t("no_messages", "ｼｰﾝ...静かな場所ですね。")}</p>`;
    container.appendChild(div);
  } else {
    const msgMap = {};
    const roots = [];
    messages.forEach((m) => {
      m.children = [];
      msgMap[m.id] = m;
    });
    messages.forEach((m) => {
      if (m.reply_to_id && msgMap[m.reply_to_id]) {
        msgMap[m.reply_to_id].children.push(m);
      } else {
        roots.push(m);
      }
    });
    roots.forEach((root) => renderMessageNode(root, container, context, callbacks));
  }
  container.scrollTop = container.scrollHeight;
  return messages;
}

export async function loadGroupMessages(threadId, container, context, callbacks) {
  const msgs = await api(`get_group_messages&thread_id=${threadId}`);
  container.innerText = "";

  if (msgs.error) {
    console.error("Failed to load group messages:", msgs.error);
    return;
  }

  if (msgs.length === 0) {
    const div = document.createElement("div");
    div.className = "empty-state";
    div.innerHTML = `<p>${t("no_group_messages", "グループメッセージはありません。")}</p>`;
    container.appendChild(div);
  } else {
    const msgMap = {};
    const roots = [];
    msgs.forEach((m) => {
      m.children = [];
      msgMap[m.id] = m;
    });
    msgs.forEach((m) => {
      if (m.reply_to_id && msgMap[m.reply_to_id]) {
        msgMap[m.reply_to_id].children.push(m);
      } else {
        roots.push(m);
      }
    });
    roots.forEach((root) => renderMessageNode(root, container, context, callbacks));
  }
  container.scrollTop = container.scrollHeight;
  return msgs;
}

/**
 * Search messages with filters
 */
export async function searchMessages() {
  const query = document.getElementById("search-input").value.trim();
  const hasAttachment = document.getElementById("search-has-attachment").checked;
  const dateFrom = document.getElementById("search-date-from").value;
  const dateTo = document.getElementById("search-date-to").value;

  if (!query && !hasAttachment && !dateFrom && !dateTo) return;

  // クエリパラメータを直接渡す（api()は index.php?api= を前置する）
  let apiPath = `search_messages`;
  if (query) apiPath += `&q=${encodeURIComponent(query)}`;
  if (hasAttachment) apiPath += `&has_attachment=1`;
  if (dateFrom) apiPath += `&date_from=${encodeURIComponent(dateFrom)}`;
  if (dateTo) apiPath += `&date_to=${encodeURIComponent(dateTo)}`;

  const results = await api(apiPath);
  const list = document.getElementById("search-results-list");
  if (!list) return;

  list.innerHTML = "";
  if (!results || results.length === 0) {
    list.innerHTML = `<div class="empty-state">${t("no_results", "見つかりませんでした。")}</div>`;
  } else {
    results.forEach(m => {
      const item = document.createElement("div");
      item.className = "search-result-item";
      item.innerHTML = `
        <div class="result-meta">
          <span class="result-user">${m.username || m.user || ''}</span>
          <span class="result-date">${m.created_at || ''}</span>
        </div>
        <div class="result-content">${m.content || ''}</div>
      `;
      item.onclick = () => {
        window.switchThread(m.thread_id, m.thread_name || m.thread_id);
        document.getElementById("search-results-overlay").classList.remove("active");
      };
      list.appendChild(item);
    });
  }

  document.getElementById("search-results-overlay").classList.add("active");
}

/**
 * Toggle thread favorite status
 * @param {number} threadId 
 */
export async function toggleFavorite(threadId) {
  const res = await api(`toggle_favorite&thread_id=${threadId}`, "POST");
  if (res && res.success) {
    const btn = document.getElementById("fav-btn");
    if (btn) btn.innerText = res.is_favorite ? "★" : "☆";
  }
}

/**
 * Edit current thread settings
 */
export async function editCurrentThread() {
  const name = document.getElementById("current-thread-name").innerText;
  const newName = prompt(t("edit_thread_prompt", "新しいスレッド名を入力してください"), name);
  if (newName && newName !== name) {
    const res = await api(`edit_thread&id=${window.SYCS_CONFIG.currentThreadId}`, "POST", { name: newName });
    if (res && res.success) {
      location.reload();
    }
  }
}

/**
 * Delete current thread
 */
export async function deleteCurrentThread() {
  if (confirm(t("delete_thread_confirm", "このスレッドを削除してもよろしいですか？"))) {
    const res = await api(`delete_thread&id=${window.SYCS_CONFIG.currentThreadId}`, "POST");
    if (res && res.success) {
      // Switch to general or reload
      window.switchThread(1, "general");
    }
  }
}

/**
 * Switch between thread and group tabs in sidebar
 * @param {string} tab 
 */
export function switchSidebarTab(tab) {
  const threads = document.getElementById("thread-list");
  const groups = document.getElementById("group-list");
  const threadArea = document.getElementById("create-thread-area");
  const groupArea = document.getElementById("create-group-area");
  
  if (tab === "threads") {
    if (threads) threads.style.display = "block";
    if (groups) groups.style.display = "none";
    if (threadArea) threadArea.style.display = "block";
    if (groupArea) groupArea.style.display = "none";
    loadThreads(th => window.switchThread(th.id, th.name, th.creator_id));
  } else {
    if (threads) threads.style.display = "none";
    if (groups) groups.style.display = "block";
    if (threadArea) threadArea.style.display = "none";
    if (groupArea) groupArea.style.display = "block";
    loadGroupThreads(g => window.switchThread(g.id, g.name, g.creator_id));
  }

  document.querySelectorAll(".sidebar-tabs .tab-btn").forEach(btn => {
    const onclickAttr = btn.getAttribute("onclick") || "";
    btn.classList.toggle("active", onclickAttr.includes(tab));
  });
}

/**
 * Create a new thread
 */
export async function createThread() {
  const input = document.getElementById("new-thread-name");
  const name = input.value.trim();
  if (!name) return;

  const res = await api("create_thread", "POST", { name });
  if (res && res.success) {
    input.value = "";
    loadThreads(th => window.switchThread(th.id, th.name, th.creator_id));
  }
}

/**
 * Save thread settings (name, category, webhook)
 */
export async function saveThreadSettings() {
  const name = document.getElementById("settings-thread-name").value;
  const category = document.getElementById("settings-thread-category").value;
  const webhook = document.getElementById("settings-thread-webhook").value;

  const res = await api(`update_thread`, "POST", {
    thread_id: window.SYCS_CONFIG.currentThreadId,
    name,
    category,
    discord_webhook_url: webhook
  });

  if (res && res.success) {
    showToast(t("success", "成功"), t("thread_updated", "スレッド情報を更新しました"), "success");
    location.reload();
  }
}

/**
 * Show Group Creation Dialog
 */
export async function showGroupCreationDialog() {
  const modal = document.getElementById("group-creation-modal");
  const picker = document.getElementById("group-member-picker");
  if (!modal || !picker) return;

  picker.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
  const friends = await api("get_friends");
  picker.innerHTML = "";
  if (friends && friends.length > 0) {
    friends.forEach(f => {
      const div = document.createElement("div");
      div.className = "member-picker-item";
      div.innerHTML = `
        <label>
          <input type="checkbox" name="members" value="${f.id}"> ${f.username}
        </label>
      `;
      picker.appendChild(div);
    });
  } else {
    picker.innerHTML = `<div class="empty-state">${t("no_friends", "フレンドがいません")}</div>`;
  }
  modal.showModal();
}

/**
 * Submit Group Creation
 */
export async function submitGroupCreation() {
  const name = document.getElementById("group-chat-name").value.trim();
  const checkboxes = document.querySelectorAll("#group-member-picker input[name='members']:checked");
  const members = Array.from(checkboxes).map(cb => cb.value);

  if (!name || members.length === 0) {
    showToast(t("error", "エラー"), t("group_creation_invalid", "名前とメンバーを入力してください"), "error");
    return;
  }

  const res = await api("create_group", "POST", { name, members });
  if (res && res.success) {
    location.reload();
  }
}

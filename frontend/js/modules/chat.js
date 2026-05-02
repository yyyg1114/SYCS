/**
 * SYCS Chat Module
 */

import { api } from './api.js';
import { renderMessageNode } from './message.js';
import { getSkeletonLoader, t } from './utils.js';

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

  const params = new URLSearchParams({
    api: "search_messages",
    q: query,
    has_attachment: hasAttachment ? 1 : 0,
    date_from: dateFrom,
    date_to: dateTo
  });

  const results = await api(params.toString());
  const list = document.getElementById("search-results-list");
  if (!list) return;

  list.innerHTML = "";
  if (results.length === 0) {
    list.innerHTML = `<div class="empty-state">${t("no_results", "見つかりませんでした。")}</div>`;
  } else {
    results.forEach(m => {
      const item = document.createElement("div");
      item.className = "search-result-item";
      item.innerHTML = `
        <div class="result-meta">
          <span class="result-user">${m.user}</span>
          <span class="result-date">${m.created_at}</span>
        </div>
        <div class="result-content">${m.content}</div>
      `;
      item.onclick = () => {
        // Scroll to message or load thread
        window.switchThread(m.thread_id, m.thread_name);
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

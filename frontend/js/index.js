let currentThreadId = window.SYCS_CONFIG.currentThreadId;
let currentThreadCreatorId = window.SYCS_CONFIG.currentThreadCreatorId;
let currentThreadWebhookUrl = null;
let currentThreadCategory = "General";
const currentUserId = window.SYCS_CONFIG.currentUserId;
const currentUserName = window.SYCS_CONFIG.currentUserName;
const currentUserTheme = window.SYCS_CONFIG.currentUserTheme;
let userKeywords = window.SYCS_CONFIG.userKeywords;
const translations = window.SYCS_CONFIG.translations || {};

/**
 * Translate a key
 * @param {string} key
 * @param {string} defaultText
 * @returns {string}
 */
function t(key, defaultText = null) {
  return translations[key] || defaultText || key;
}

// Apply theme on early load
if (currentUserTheme.theme === "light")
  document.body.classList.add("light-theme");
if (currentUserTheme.accentColor) {
  document.documentElement.style.setProperty(
    "--accent-color",
    currentUserTheme.accentColor,
  );
  const r = parseInt(currentUserTheme.accentColor.slice(1, 3), 16);
  const g = parseInt(currentUserTheme.accentColor.slice(3, 5), 16);
  const b = parseInt(currentUserTheme.accentColor.slice(5, 7), 16);
  document.documentElement.style.setProperty(
    "--accent-hover",
    `rgba(${r}, ${g}, ${b}, 0.8)`,
  );
}

// DM State
let currentPartnerId = null;
let currentGroupThreadId = null;
let isDmMode = false;
let isGroupMode = false;
let dmFileToUpload = null;
const csrfToken = window.SYCS_CONFIG.csrfToken;
let replyToId = null;
let fileToUpload = null;
let mutedTargets = new Set();

let lastMessageId = 0;
let lastDmId = 0;
let isWindowFocused = true;
window.onfocus = () => {
  isWindowFocused = true;
};
window.onblur = () => {
  isWindowFocused = false;
};

// DOM Elements
const msgInput = document.getElementById("msg-input");
const replyBar = document.getElementById("reply-bar");
const uploadPreview = document.getElementById("upload-preview");
const previewContent = document.getElementById("preview-content");

// Helper to escape HTML to prevent XSS
function escapeHTML(str) {
  if (str === null || str === undefined) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/**
 * Helper to apply a regex rule to text nodes within a fragment and replace match with elements
 * @param {DocumentFragment} fragment
 * @param {RegExp} regex
 * @param {Function} elementFactory
 */
function applyRule(fragment, regex, elementFactory) {
  const walker = document.createTreeWalker(
    fragment,
    NodeFilter.SHOW_TEXT,
    null,
    false,
  );
  const textNodes = [];
  while (walker.nextNode()) textNodes.push(walker.currentNode);

  for (const node of textNodes) {
    // Skip if already inside a pre or code tag to avoid nested formatting or breaking code blocks
    let parent = node.parentElement;
    let inProtectedTag = false;
    while (parent && parent !== fragment) {
      if (parent.tagName === "CODE" || parent.tagName === "PRE") {
        inProtectedTag = true;
        break;
      }
      parent = parent.parentElement;
    }
    if (inProtectedTag) continue;

    const text = node.nodeValue;
    let lastIndex = 0;
    let match;
    const newNodes = [];
    let hasMatch = false;

    while ((match = regex.exec(text)) !== null) {
      hasMatch = true;
      if (match.index > lastIndex) {
        newNodes.push(
          document.createTextNode(text.substring(lastIndex, match.index)),
        );
      }
      const element = elementFactory(...match);
      if (element) {
        newNodes.push(element);
      }
      lastIndex = regex.lastIndex;
      if (!regex.global) break;
    }

    if (hasMatch) {
      if (lastIndex < text.length) {
        newNodes.push(document.createTextNode(text.substring(lastIndex)));
      }
      const p = node.parentNode;
      for (const newNode of newNodes) {
        p.insertBefore(newNode, node);
      }
      p.removeChild(node);
    }
    regex.lastIndex = 0;
  }
}

/**
 * Format a message string into a DocumentFragment with rich text elements
 * @param {string} text
 * @returns {DocumentFragment}
 */
function formatMessage(text) {
  const fragment = document.createDocumentFragment();
  if (!text) return fragment;

  // Add initial text node
  fragment.appendChild(document.createTextNode(text));

  // 1. Code Blocks: ```lang\ncode\n```
  applyRule(fragment, /```(\w*)\n([\s\S]*?)\n```/g, (match, lang, code) => {
    const pre = document.createElement("pre");
    const codeEl = document.createElement("code");
    if (lang) codeEl.className = `language-${lang}`;
    codeEl.textContent = code.trim();
    pre.appendChild(codeEl);
    return pre;
  });

  // 2. Inline Code: `code`
  applyRule(fragment, /`([^`]+)`/g, (match, code) => {
    const codeEl = document.createElement("code");
    codeEl.textContent = code;
    return codeEl;
  });

  // 3. Bold: **text**
  applyRule(fragment, /\*\*([^*]+)\*\*/g, (match, content) => {
    const b = document.createElement("b");
    b.textContent = content;
    return b;
  });

  // 4. Italic: *text* or _text_
  applyRule(fragment, /\*([^*]+)\*/g, (match, content) => {
    const i = document.createElement("i");
    i.textContent = content;
    return i;
  });
  applyRule(fragment, /_([^_]+)_/g, (match, content) => {
    const i = document.createElement("i");
    i.textContent = content;
    return i;
  });

  // 5. Underline: __text__
  applyRule(fragment, /__([^_]+)__/g, (match, content) => {
    const u = document.createElement("u");
    u.textContent = content;
    return u;
  });

  // 6. Strikethrough: ~~text~~
  applyRule(fragment, /~~([^~]+)~~/g, (match, content) => {
    const del = document.createElement("del");
    del.textContent = content;
    return del;
  });

  // 7. Blockquotes: > text
  applyRule(fragment, /^> (.*$)/gm, (match, content) => {
    const bq = document.createElement("blockquote");
    bq.textContent = content;
    return bq;
  });

  // 8. Mentions: @username
  applyRule(fragment, /@([a-zA-Z0-9_]+)/g, (match, username) => {
    const span = document.createElement("span");
    const isMe =
      typeof currentUserName !== "undefined" && username === currentUserName;
    span.className = `mention${isMe ? " mention-me" : ""}`;
    span.textContent = match;
    return span;
  });

  // 9. Auto-link URLs
  const urlRegex = /(https?:\/\/[^\s<]+)/g;
  applyRule(fragment, urlRegex, (match, url) => {
    const a = document.createElement("a");
    a.href = url;
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.textContent = url;
    return a;
  });

  // 10. Line breaks (preserve in code blocks)
  applyRule(fragment, /\n/g, () => {
    return document.createElement("br");
  });

  return fragment;
}

function applyHighlighting(container) {
  if (typeof hljs !== 'undefined') {
    container.querySelectorAll('pre code').forEach((block) => {
      hljs.highlightElement(block);
    });
  }
}

// --- Markdown logic removed for strict security via innerText ---

function getAvatarElement(name, status = "none", avatarUrl = null) {
  const initial = name ? name.charAt(0).toUpperCase() : "?";
  const colors = [
    "#6366f1",
    "#ec4899",
    "#8b5cf6",
    "#10b981",
    "#f59e0b",
    "#3b82f6",
  ];
  const colorIdx = (name ? name.length : 0) % colors.length;

  const container = document.createElement("div");
  container.className = "avatar-container";

  const div = document.createElement("div");
  div.className = "avatar";

  if (avatarUrl) {
    const img = document.createElement("img");
    img.src = avatarUrl;
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.borderRadius = "50%";
    img.style.objectFit = "cover";
    div.appendChild(img);
  } else {
    div.style.background = colors[colorIdx];
    div.textContent = initial;
  }

  container.appendChild(div);

  if (status !== "none") {
    const indicator = document.createElement("div");
    indicator.className = `status-indicator status-${status}`;
    container.appendChild(indicator);
  }

  return container;
}

function getSkeletonLoader() {
  const container = document.createElement("div");
  container.className = "skeleton-container";
  for (let i = 0; i < 4; i++) {
    const item = document.createElement("div");
    item.className = "skeleton-item";

    const avatar = document.createElement("div");
    avatar.className = "skeleton-avatar skeleton-shimmer";

    const info = document.createElement("div");
    info.className = "skeleton-info";

    const name = document.createElement("div");
    name.className = "skeleton-name skeleton-shimmer";

    const text1 = document.createElement("div");
    text1.className = "skeleton-text skeleton-shimmer";

    const text2 = document.createElement("div");
    text2.className = "skeleton-text short skeleton-shimmer";

    info.appendChild(name);
    info.appendChild(text1);
    info.appendChild(text2);

    item.appendChild(avatar);
    item.appendChild(info);
    container.appendChild(item);
  }
  return container;
}

async function updateMyStatus(status) {
  const body = new FormData();
  body.append("status", status);
  const res = await api("update_status", "POST", body);
  if (res && res.success) {
    // Update Indicator
    const indicator = document.getElementById("global-status-indicator");
    if (indicator) indicator.className = `status-indicator status-${status}`;

    // Sync Inputs
    const sidebarInput = document.getElementById("sidebar-status-input");
    const modalInput = document.getElementById("modal-status-input");
    if (sidebarInput) {
      sidebarInput.value = status;
    }
    if (modalInput) modalInput.value = status;
  }
}

/**
 * トースト通知を表示する
 * @param {string} title タイトル
 * @param {string} message メッセージ内容
 * @param {string} type 'success', 'error', 'warning'
 * @param {number} duration 表示時間（ミリ秒）
 */
function showToast(title, message, type = "success", duration = 5000) {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  const iconMap = {
    success:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    error:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    warning:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
  };

  toast.innerHTML = `
        <div class="toast-icon">${iconMap[type] || ""}</div>
        <div class="toast-content">
            <div class="toast-title">${escapeHTML(title)}</div>
            <div class="toast-message">${escapeHTML(message)}</div>
        </div>
        <div class="toast-close">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </div>
    `;

  container.appendChild(toast);

  const removeToast = () => {
    if (toast.classList.contains("removing")) return;
    toast.classList.add("removing");
    setTimeout(() => {
      if (toast.parentNode === container) {
        container.removeChild(toast);
      }
    }, 300);
  };

  toast.querySelector(".toast-close").onclick = removeToast;

  if (duration > 0) {
    setTimeout(removeToast, duration);
  }
}

async function api(path, method = "GET", body = null) {
  const opts = {
    method,
  };
  if (method === "POST") {
    if (!body) body = new FormData();

    if (!(body instanceof FormData)) {
      const formData = new FormData();
      for (const key in body) {
        formData.append(key, body[key]);
      }
      body = formData;
    }

    // Auto-append CSRF token
    if (!body.has("csrf_token")) {
      body.append("csrf_token", csrfToken || window.SYCS_CONFIG.csrfToken);
    }
    opts.body = body;
  } else if (body) {
    opts.body = body;
  }

  try {
    const res = await fetch(`index.php?api=${path}`, opts);

    // Get response text first
    const text = await res.text();

    // Try to parse as JSON
    try {
      const json = JSON.parse(text);
      if (json && json.success === false) {
        showToast(
          t("error", "エラー"),
          json.error || t("unknown_error", "不明なエラーが発生しました"),
          "error",
        );
      }
      return json;
    } catch (parseError) {
      console.error("JSON parse error:", parseError, text);
      const errorMsg = t("server_error_json", "サーバーエラー: JSONパースに失敗しました");
      showToast(t("system_error", "システムエラー"), errorMsg, "error");
      return {
        error: errorMsg,
        details: text.substring(0, 500),
      };
    }
  } catch (fetchError) {
    console.error("Fetch error:", fetchError);
    const errorMsg = t("network_error", "ネットワークエラー") + ": " + fetchError.message;
    showToast(t("network_error", "通信エラー"), t("connection_failed", "サーバーに接続できませんでした"), "error");
    return {
      error: errorMsg,
    };
  }
}

async function loadThreads() {
  const threads = await api("get_threads");
  const list = document.getElementById("thread-list");
  list.innerText = "";

  // Group by category
  const categories = {};
  threads.forEach((t) => {
    const cat = t.category || "General";
    if (!categories[cat]) categories[cat] = [];
    categories[cat].push(t);
  });

  for (const [catName, catThreads] of Object.entries(categories)) {
    const catHeader = document.createElement("div");
    catHeader.style.padding = "10px 10px 5px 10px";
    catHeader.style.fontSize = "0.75rem";
    catHeader.style.fontWeight = "700";
    catHeader.style.color = "var(--text-secondary)";
    catHeader.style.textTransform = "uppercase";
    catHeader.innerText = catName;
    list.appendChild(catHeader);

    catThreads.forEach((t) => {
      const item = document.createElement("div");
      item.className = `thread-item ${!isGroupMode && !isDmMode && t.id == currentThreadId ? "active" : ""}`;
      item.textContent = "# " + t.name;
      item.onclick = () =>
        switchThread(
          t.id,
          t.name,
          t.creator_id,
          t.discord_webhook_url,
          t.category,
        );
      list.appendChild(item);
    });
  }
  loadGroupThreads();
}

async function loadGroupThreads() {
  const groups = await api("get_group_threads");
  const list = document.getElementById("group-list");
  if (!list) return;
  list.innerText = "";
  groups.forEach((g) => {
    const item = document.createElement("div");
    item.className = `thread-item ${isGroupMode && g.id == currentGroupThreadId ? "active" : ""}`;
    item.textContent = "👥 " + g.name;
    item.onclick = () => switchGroupThread(g.id, g.name);
    list.appendChild(item);
  });
}

function switchSidebarTab(tab) {
  const threadList = document.getElementById("thread-list");
  const groupList = document.getElementById("group-list");
  const threadCreate = document.getElementById("create-thread-area");
  const groupCreate = document.getElementById("create-group-area");
  const btns = document.querySelectorAll(".sidebar-tabs .tab-btn");

  btns.forEach((b) => b.classList.remove("active"));

  if (tab === "threads") {
    threadList.style.display = "block";
    groupList.style.display = "none";
    threadCreate.style.display = "block";
    groupCreate.style.display = "none";
    btns[0].classList.add("active");
  } else {
    threadList.style.display = "none";
    groupList.style.display = "block";
    threadCreate.style.display = "none";
    groupCreate.style.display = "block";
    btns[1].classList.add("active");
  }
}

async function showGroupCreationDialog() {
  const users = await api("get_all_users");
  const picker = document.getElementById("group-member-picker");
  picker.textContent = "";
  users.forEach((u) => {
    const label = document.createElement("label");
    label.style.display = "flex";
    label.style.alignItems = "center";
    label.style.gap = "10px";
    label.style.padding = "5px";
    label.style.cursor = "pointer";
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = "group_members";
    checkbox.value = String(u.id);
    label.appendChild(checkbox);
    label.appendChild(document.createTextNode(` ${u.username || ""}`));
    picker.appendChild(label);
  });
  document.getElementById("group-creation-modal").showModal();
}

async function submitGroupCreation() {
  const name = document.getElementById("group-chat-name").value;
  const checkboxes = document.querySelectorAll(
    'input[name="group_members"]:checked',
  );
  const ids = Array.from(checkboxes).map((cb) => parseInt(cb.value));

  if (!name) return alert(t("enter_group_name", "グループ名を入力してください"));
  if (ids.length === 0) return alert(t("select_at_least_one_member", "メンバーを1人以上選択してください"));

  const body = new FormData();
  body.append("name", name);
  body.append("participant_ids", JSON.stringify(ids));
  body.append("csrf_token", csrfToken);
  const res = await api("create_group_thread", "POST", body);
  if (res.success) {
    document.getElementById("group-creation-modal").close();
    await loadGroupThreads();
  }
}

async function switchGroupThread(id, name) {
  isGroupMode = true;
  isDmMode = false;
  currentGroupThreadId = id;
  currentThreadId = null;
  currentPartnerId = null;

  document.getElementById("current-thread-name").innerText = name;
  document
    .querySelectorAll(".thread-item")
    .forEach((el) => el.classList.remove("active"));

  const container = document.getElementById("message-container");
  container.innerText = "";
  container.appendChild(getSkeletonLoader());

  if (socket) {
    socket.emit("join_group_thread", id);
  }
  loadGroupMessages();
  updateMuteIcon();
  updateThreadActions(); // Refresh actions block
}

async function loadGroupMessages() {
  if (!currentGroupThreadId) return;
  const msgs = await api(
    `get_group_messages&thread_id=${currentGroupThreadId}`,
  );
  const container = document.getElementById("message-container");
  container.innerText = "";

  if (msgs.error) {
    alert("グループメッセージの読み込みに失敗しました: " + msgs.error);
    return;
  }
  if (msgs.length === 0) {
    const div = document.createElement("div");
    div.className = "empty-state";
    const p = document.createElement("p");
    p.textContent = t("no_group_messages", "グループメッセージはありません。");
    p.appendChild(document.createElement("br"));
    p.appendChild(document.createTextNode(t("start_new_conversation", "新しく会話を始めましょう！")));
    div.appendChild(p);
    container.appendChild(div);
    return;
  }

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

  roots.forEach((root) => renderMessageNode(root, container));

  // Notification Trigger for Groups
  // Notification Trigger for Groups: Now handled via Socket.io
  const latest = msgs[msgs.length - 1];
  // if (lastMessageId !== 0 && latest.id > lastMessageId && latest.user_id != currentUserId) {
  //     sendNotification(`新着グループメッセージ (👥 ${document.getElementById('current-thread-name').innerText})`, `${latest.username}: ${latest.content}`, 'group', currentGroupThreadId);
  // }
  lastMessageId = latest.id;

  container.scrollTop = container.scrollHeight;
}

async function switchThread(
  id,
  name,
  creatorId,
  webhookUrl = null,
  category = "General",
) {
  isGroupMode = false;
  isDmMode = false;
  currentThreadId = id;
  currentGroupThreadId = null;
  currentPartnerId = null;

  currentThreadCreatorId = creatorId;
  currentThreadWebhookUrl = webhookUrl;
  currentThreadCategory = category;
  updateThreadActions();
  document.getElementById("current-thread-name").innerText = name;
  document.querySelectorAll(".thread-item").forEach((el) => {
    el.classList.remove("active");
    if (el.textContent === "# " + name) el.classList.add("active");
  });

  const container = document.getElementById("message-container");
  container.innerText = "";
  container.appendChild(getSkeletonLoader());
  cancelReply();
  cancelUpload();
  loadMessages(1000);
  checkFavoriteStatus();
  updateMuteIcon();
  api(`set_last_thread&thread_id=${id}`);
}

function updateThreadActions() {
  const block = document.getElementById("thread-actions-block");
  if (!block) return;

  const editBtn = document.getElementById("thread-edit-btn");
  const deleteBtn = document.getElementById("thread-delete-btn");

  if (isGroupMode) {
    // Group management is allowed for all members
    block.style.display = "flex";
    if (editBtn) editBtn.style.display = "inline-flex";
    if (deleteBtn) deleteBtn.style.display = "inline-flex";
    return;
  }

  // Always show the block (for search, mute, etc.)
  block.style.display = "flex";

  if (parseInt(currentThreadId) === 1) {
    // Hide edit/delete for General thread
    if (editBtn) editBtn.style.display = "none";
    if (deleteBtn) deleteBtn.style.display = "none";
  } else {
    // Show edit/delete for other threads
    if (editBtn) editBtn.style.display = "inline-flex";
    if (deleteBtn) deleteBtn.style.display = "inline-flex";
  }
}

async function editCurrentThread() {
  document.getElementById("settings-thread-name").value =
    document.getElementById("current-thread-name").innerText;

  if (isGroupMode) {
    // For groups, we only allow renaming in this simplified UI
    document.getElementById(
      "settings-thread-webhook",
    ).parentElement.style.display = "none";
    document.getElementById(
      "settings-thread-category",
    ).parentElement.style.display = "none";
  } else {
    document.getElementById(
      "settings-thread-webhook",
    ).parentElement.style.display = "block";
    document.getElementById(
      "settings-thread-category",
    ).parentElement.style.display = "block";
    document.getElementById("settings-thread-webhook").value =
      currentThreadWebhookUrl || "";
    document.getElementById("settings-thread-category").value =
      currentThreadCategory || "General";
  }

  document.getElementById("thread-settings-modal").showModal();
}

async function saveThreadSettings() {
  const newName = document.getElementById("settings-thread-name").value;
  const webhook = document.getElementById("settings-thread-webhook").value;
  const category = document.getElementById("settings-thread-category").value;

  if (newName && newName.trim() !== "") {
    const body = new FormData();
    if (isGroupMode) {
      body.append("thread_id", currentGroupThreadId);
      body.append("name", newName.trim());
      const res = await api("edit_group_thread", "POST", body);
      if (res.success) {
        document.getElementById("thread-settings-modal").close();
        await loadGroupThreads();
        document.getElementById("current-thread-name").innerText =
          newName.trim();
      } else {
        alert("保存に失敗しました: " + (res.error || "Unknown"));
      }
    } else {
      body.append("thread_id", currentThreadId);
      body.append("name", newName.trim());
      body.append("discord_webhook_url", webhook.trim());
      body.append("category", category.trim());
      const res = await api("edit_thread", "POST", body);
      if (res.success) {
        document.getElementById("thread-settings-modal").close();
        await loadThreads();
        switchThread(
          currentThreadId,
          newName.trim(),
          currentThreadCreatorId,
          webhook.trim(),
          category.trim(),
        );
      } else {
        alert("保存に失敗しました: " + (res.error || "Unknown"));
      }
    }
  }
}

async function deleteCurrentThread() {
  if (
    confirm(
      isGroupMode
        ? t("confirm_delete_group", "本当にこのグループを削除しますか？")
        : t("confirm_delete_thread", "本当にこのスレッドを削除しますか？"),
    )
  ) {
    const body = new FormData();
    if (isGroupMode) {
      body.append("thread_id", currentGroupThreadId);
      const res = await api("delete_group_thread", "POST", body);
      if (res.success) {
        location.reload();
      } else {
        alert("削除に失敗しました: " + (res.error || "Unknown"));
      }
    } else {
      body.append("thread_id", currentThreadId);
      const res = await api("delete_thread", "POST", body);
      if (res.success) {
        location.reload();
      } else {
        alert("削除に失敗しました: " + (res.error || "Unknown"));
      }
    }
  }
}

// --- Profile Logic ---
function showProfileModal() {
  document.getElementById("profile-modal").showModal();
  if (typeof locationManager !== "undefined") {
    locationManager.updateDisplay();
    locationManager.getCurrentLocation();
  }
}

function updatePreviewBanner(color) {
  document.getElementById("preview-banner").style.background = color;
}

function updatePreviewBio(text) {
  document.getElementById("preview-bio").innerText = text;
}

function updatePreviewStatus(status) {
  const indicator = document.getElementById("preview-status-indicator");
  indicator.className = `discord-status-indicator status-${status}`;
}

function updatePreviewLayout(layout) {
  const card = document.getElementById("profile-preview-card");
  if (card) {
    card.setAttribute("data-layout", layout);
  }
}

let shouldRemoveAvatar = false;
let shouldRemoveBanner = false;

function previewBannerImage(input) {
  if (input.files && input.files[0]) {
    shouldRemoveBanner = false;
    const reader = new FileReader();
    reader.onload = function (e) {
      const banner = document.getElementById("preview-banner");
      banner.style.background = `url('${e.target.result}') center/cover`;
      document.getElementById("btn-remove-banner").style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeBannerPreview() {
  shouldRemoveBanner = true;
  document.getElementById("edit-banner-img-input").value = "";
  const banner = document.getElementById("preview-banner");
  const defaultColor = document.getElementById("edit-banner-input").value;
  banner.style.background = defaultColor;
  document.getElementById("btn-remove-banner").style.display = "none";
}

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    shouldRemoveAvatar = false;
    const reader = new FileReader();
    reader.onload = function (e) {
      const container = document.getElementById("preview-avatar-container");
      container.textContent = "";
      const img = document.createElement("img");
      img.src = e.target.result;
      img.className = "discord-avatar";
      img.id = "preview-avatar-img";
      container.appendChild(img);
      document.getElementById("btn-remove-avatar").style.display =
        "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeAvatarPreview() {
  shouldRemoveAvatar = true;
  document.getElementById("edit-avatar-input").value = "";
  const container = document.getElementById("preview-avatar-container");
  container.textContent = currentUserName
    ? currentUserName.charAt(0).toUpperCase()
    : "?";
  container.style.background = "#6366f1";
  document.getElementById("btn-remove-avatar").style.display = "none";
}

async function saveProfile() {
  try {
    console.log("[Profile] Saving profile...");
    const bannerColor = document.getElementById("edit-banner-input")?.value || "";
    const twitter = document.getElementById("edit-twitter-input")?.value || "";
    const github = document.getElementById("edit-github-input")?.value || "";
    const status = document.getElementById("modal-status-input")?.value || "online";
    const avatarInput = document.getElementById("edit-avatar-input");
    const bannerImgInput = document.getElementById("edit-banner-img-input");
    const avatarFile = avatarInput?.files?.[0];
    const bannerFile = bannerImgInput?.files?.[0];
    const profileLayout = document.getElementById("edit-layout-input")?.value || "classic";
    const accentColor = document.getElementById("edit-accent-input")?.value || "#6366f1";
    const keywords = document.getElementById("edit-keywords-input")?.value || "";
    const bio = document.getElementById("edit-bio-input")?.value || "";

    const theme = document.body.classList.contains("light-theme") ? "light" : "dark";

    const body = new FormData();
    body.append("csrf_token", window.SYCS_CONFIG?.csrfToken || "");
    body.append("bio", bio);
    body.append("banner_color", bannerColor);
    body.append("status", status);
    body.append("notification_keywords", keywords);
    body.append("profile_layout", profileLayout);
    body.append("social_links", JSON.stringify({ twitter, github }));
    body.append("theme_preference", JSON.stringify({ theme, accentColor }));
    body.append("remove_avatar", !!shouldRemoveAvatar);
    body.append("remove_banner", !!shouldRemoveBanner);

    if (avatarFile) body.append("avatar", avatarFile);
    if (bannerFile) body.append("banner", bannerFile);

    const res = await api("update_profile", "POST", body);
    if (res.success) {
      alert(t("profile_updated", "プロフィールを更新しました"));
      location.reload();
    } else {
      alert(t("update_failed", "更新に失敗しました") + ": " + (res.error || t("unknown_error", "不明なエラー")));
    }
  } catch (err) {
    console.error("[Profile] Save error:", err);
    alert("エラーが発生しました: " + err.message);
  }
}

// --- User Profile View Logic ---
async function showUserProfile(userId, username) {
  // 自分自身の場合は自分用のモーダルを開く
  if (parseInt(userId) === currentUserId) {
    showProfileModal();
    return;
  }

  const modal = document.getElementById("user-profile-modal");
  const res = await api(`get_user_profile&user_id=${userId}`);

  if (res.error) {
    alert("ユーザー情報の取得に失敗しました");
    return;
  }

  const card = document.getElementById("user-profile-card");
  if (card) {
    card.setAttribute("data-layout", res.profile_layout || "classic");
  }

  // バナー
  const bannerEl = document.getElementById("user-profile-banner");
  if (res.banner_url) {
    bannerEl.style.background = `url('${res.banner_url}') center/cover`;
  } else {
    bannerEl.style.background = res.banner_color || "#6366f1";
  }

  // アバター
  const avatarContainer = document.getElementById(
    "user-profile-avatar-container",
  );
  if (res.avatar_url) {
    avatarContainer.textContent = "";
    const img = document.createElement("img");
    img.src = res.avatar_url;
    img.className = "discord-avatar";
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.borderRadius = "50%";
    img.style.objectFit = "cover";
    avatarContainer.appendChild(img);
  } else {
    const initial = res.username ? res.username.charAt(0).toUpperCase() : "?";
    avatarContainer.textContent = initial;
    avatarContainer.style.background = "#6366f1";
  }

  // ステータスインジケーター
  const statusIndicator = document.getElementById(
    "user-profile-status-indicator",
  );
  statusIndicator.className = `discord-status-indicator status-${res.status || "offline"}`;

  // ユーザー名
  document.getElementById("user-profile-username").innerText = res.username;

  // カスタムステータス
  document.getElementById("user-profile-custom-status").innerText =
    res.custom_status || "";

  // Bio
  document.getElementById("user-profile-bio").innerText =
    res.bio || "自己紹介はまだありません";

  // SNS
  const snsContainer = document.getElementById("user-profile-sns");
  snsContainer.textContent = "";
  const links = JSON.parse(res.social_links || "{}");
  if (links.twitter) {
    const a = document.createElement("a");
    a.href = `https://twitter.com/${links.twitter}`;
    a.target = "_blank";
    a.style.color = "#1DA1F2";
    a.innerText = "Twitter";
    snsContainer.appendChild(a);
  }
  if (links.github) {
    const a = document.createElement("a");
    a.href = `https://github.com/${links.github}`;
    a.target = "_blank";
    a.style.color = "#f0f6fc";
    a.innerText = "GitHub";
    snsContainer.appendChild(a);
  }
  if (!links.twitter && !links.github) {
    snsContainer.innerText = "連携なし";
  }

  // DMボタンの設定
  const dmBtn = document.getElementById("user-profile-dm-btn");
  dmBtn.onclick = () => {
    modal.close();
    // DMタブに切り替えてチャットを開始
    document.querySelector('.nav-item[data-tab="dm"]').click();
    switchToDmChat(res.id, res.username, res.avatar_url, res.status);
  };

  modal.showModal();
}

async function loadMessages(minDelay = 0) {
  const startTime = Date.now();
  const messages = await api(`get_messages&thread_id=${currentThreadId}`);

  if (minDelay > 0) {
    const elapsed = Date.now() - startTime;
    const remaining = minDelay - elapsed;
    if (remaining > 0) await new Promise((r) => setTimeout(r, remaining));
  }
  const container = document.getElementById("message-container");
  // Auto-scroll logic needs to be smarter or just stick to bottom if already at bottom
  const isAtBottom =
    container.scrollHeight - container.scrollTop <=
    container.clientHeight + 100;

  container.innerText = ""; // Clear safely
  if (messages.error) {
    alert("メッセージの読み込みに失敗しました: " + messages.error);
    return;
  }
  if (messages.length === 0) {
    const p = document.createElement("p");
    p.innerText = "ｼｰﾝ...静かな場所ですね。\n少し世間話でもどうでしょうか?";
    const div = document.createElement("div");
    div.className = "empty-state";
    div.appendChild(p);
    container.appendChild(div);
  } else {
    // Build Tree
    const msgMap = {};
    const roots = [];

    // 1. Init map
    messages.forEach((m) => {
      m.children = [];
      msgMap[m.id] = m;
    });

    // 2. Assign children
    messages.forEach((m) => {
      if (m.reply_to_id && msgMap[m.reply_to_id]) {
        msgMap[m.reply_to_id].children.push(m);
      } else {
        roots.push(m);
      }
    });

    // 3. Recursive Render
    roots.forEach((root) => renderMessageNode(root, container));

    // Notification Trigger
    // Notification Trigger: Now handled via Socket.io
    const latest = messages[messages.length - 1];
    // if (lastMessageId !== 0 && latest.id > lastMessageId && latest.user_id != currentUserId) {
    //     sendNotification(`新着メッセージ (#${document.getElementById('current-thread-name').innerText})`, `${latest.username}: ${latest.content}`, 'thread', currentThreadId);
    // }
    lastMessageId = latest.id;
  }

  if (isAtBottom) container.scrollTop = container.scrollHeight;
}

function renderMessageNode(m, parentContainer) {
  // Wrapper for indentation
  const wrapper = document.createElement("div");
  wrapper.className = "message-wrapper";
  // If it's a child (implied by context, but we handle visual indent via nesting divs)
  // We create the message group, then a child container.

  // Add ID for jumping
  wrapper.id = "message-" + m.id;

  const group = document.createElement("div");
  group.className = "message-group";

  // Avatar
  group.appendChild(
    getAvatarElement(m.username, m.status || "online", m.avatar_url),
  );

  const info = document.createElement("div");
  info.className = "message-info";

  const header = document.createElement("div");
  header.className = "message-header";

  const user = document.createElement("span");
  user.className = "message-user clickable-username";
  user.textContent = m.username;
  user.style.cursor = "pointer";
  user.onclick = (e) => {
    e.stopPropagation();
    showUserProfile(m.user_id, m.username);
  };

  const time = document.createElement("span");
  time.className = "message-time";
  time.textContent = m.created_at;

  // Actions
  const actions = document.createElement("div");
  actions.className = "message-actions";

  // Always allow reply
  const replyBtn = document.createElement("button");
  replyBtn.className = "msg-action-btn";
  const replyImg = document.createElement("img");
  replyImg.src = "assets/img/reply.svg";
  replyImg.alt = "返信";
  replyImg.style.width = "16px";
  replyImg.style.height = "16px";
  replyBtn.appendChild(replyImg);
  replyBtn.title = "返信";
  replyBtn.onclick = () => startReply(m.id, m.username, m.content);
  actions.appendChild(replyBtn);

  // Pin Button
  const isPinned = !!+m.is_pinned;
  const pinBtn = document.createElement("button");
  pinBtn.className = "msg-action-btn";
  if (isPinned) {
    pinBtn.textContent = "📍";
  } else {
    const pinImg = document.createElement("img");
    pinImg.src = "assets/img/pin.svg";
    pinImg.alt = "ピン";
    pinImg.style.width = "16px";
    pinImg.style.height = "16px";
    pinImg.style.opacity = "0.6";
    pinBtn.appendChild(pinImg);
  }
  pinBtn.title = isPinned ? "ピン解除" : "ピン留め";
  pinBtn.onclick = () => togglePin(m.id);
  actions.appendChild(pinBtn);

  // Reaction Button
  const reactBtn = document.createElement("button");
  reactBtn.className = "msg-action-btn";
  const reactImg = document.createElement("img");
  reactImg.src = "assets/img/emoji.svg";
  reactImg.alt = "リアクション";
  reactImg.style.width = "16px";
  reactImg.style.height = "16px";
  reactImg.style.opacity = "0.6";
  reactBtn.appendChild(reactImg);
  reactBtn.title = "リアクション";
  reactBtn.onclick = (e) => showEmojiPicker(e, m.id);
  actions.appendChild(reactBtn);

  // Add Delete/Edit buttons only if owner
  if (m.username === currentUserName) {
    const editBtn = document.createElement("button");
    editBtn.className = "msg-action-btn";
    const editImg = document.createElement("img");
    editImg.src = "assets/img/edit.svg";
    editImg.alt = "編集";
    editImg.style.width = "16px";
    editImg.style.height = "16px";
    editBtn.appendChild(editImg);
    editBtn.title = "編集";
    editBtn.onclick = () => startEditMessage(m, false);
    actions.appendChild(editBtn);

    const delBtn = document.createElement("button");
    delBtn.className = "msg-action-btn";
    delBtn.textContent = "";
    const delImg = document.createElement("img");
    delImg.src = "assets/img/trash.svg";
    delImg.alt = "削除";
    delImg.style.width = "16px";
    delImg.style.height = "16px";
    delBtn.appendChild(delImg);
    delBtn.title = "削除";
    delBtn.onclick = () => deleteMessage(m.id);
    actions.appendChild(delBtn);
  }

  header.appendChild(user);
  header.appendChild(time);
  header.appendChild(actions);

  // If it's a reply but NOT the direct child in visual tree (redundant check but safe)
  // Or just always show who it's replying to if it's not a root message
  if (m.reply_to_id && m.reply_username) {
    const quote = document.createElement("div");
    quote.className = "reply-quote";
    quote.style.cursor = "pointer";
    const replyPrefix = document.createElement("span");
    replyPrefix.style.opacity = "0.6";
    replyPrefix.style.fontSize = "0.8rem";
    replyPrefix.textContent = "↩️ 返信先: ";
    quote.appendChild(replyPrefix);

    const replyUser = document.createElement("strong");
    replyUser.textContent = m.reply_username;
    quote.appendChild(replyUser);
    quote.onclick = () => {
      const target = document.getElementById("message-" + m.reply_to_id);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
        target.style.backgroundColor = "rgba(99, 102, 241, 0.2)";
        setTimeout(() => (target.style.backgroundColor = ""), 2000);
      }
    };
    info.appendChild(quote);
  }

  // Content
  const contentDiv = document.createElement("div");
  contentDiv.className = "message-content";

  // Render Rich Text / Markdown
  contentDiv.replaceChildren(formatMessage(m.content || ""));
  applyHighlighting(contentDiv);

  if (m.is_edited == 1) {
    const editedLabel = document.createElement("span");
    editedLabel.style.fontSize = "0.7rem";
    editedLabel.style.opacity = "0.5";
    editedLabel.style.marginLeft = "5px";
    editedLabel.innerText = "(編集済み)";
    contentDiv.appendChild(editedLabel);
  }

  if (m.attachment_path) {
    const ext = m.attachment_path.split(".").pop().toLowerCase();
    const isImage = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(ext);
    const isAudio = ["mp3", "wav", "ogg"].includes(ext);
    const isVideo = ["mp4", "webm", "ogv", "mov", "avi"].includes(ext);

    if (isImage) {
      const img = document.createElement("img");
      img.src = m.attachment_path;
      img.className = "preview-img";
      img.style.display = "block";
      img.style.marginTop = "10px";
      img.onclick = () => window.open(m.attachment_path, "_blank");
      contentDiv.appendChild(img);
    } else if (isAudio) {
      const audio = document.createElement("audio");
      audio.src = m.attachment_path;
      audio.controls = true;
      audio.style.display = "block";
      audio.style.marginTop = "10px";
      audio.style.maxWidth = "100%";
      contentDiv.appendChild(audio);
    } else if (isVideo) {
      const video = document.createElement("video");
      video.src = m.attachment_path;
      video.controls = true;
      video.style.display = "block";
      video.style.marginTop = "10px";
      video.style.maxWidth = "100%";
      contentDiv.appendChild(video);
    }

    const dlLink = document.createElement("a");
    const fileName = m.attachment_path.split("/").pop();
    dlLink.href = "download.php?file=" + fileName;
    dlLink.target = "_blank";
    dlLink.innerText = "⬇️ ダウンロード";
    dlLink.style.display = "inline-block";
    dlLink.style.fontSize = "0.75rem";
    dlLink.style.marginTop = "5px";
    dlLink.style.color = "var(--accent-color)";
    contentDiv.appendChild(dlLink);
  }

  info.appendChild(header);

  // Pinned Badge
  if (!!+m.is_pinned) {
    const pinBadge = document.createElement("div");
    pinBadge.className = "message-pinned-badge";
    pinBadge.textContent = "📌 ピン留めされたメッセージ";
    info.appendChild(pinBadge);
    group.classList.add("message-pinned");
  }

  info.appendChild(contentDiv);

  // Reactions Display
  if (m.reactions && m.reactions.length > 0) {
    const reactContainer = document.createElement("div");
    reactContainer.className = "reactions-container";

    // Group by emoji
    const grouped = {};
    m.reactions.forEach((r) => {
      if (!grouped[r.emoji]) grouped[r.emoji] = [];
      grouped[r.emoji].push(r.user_id);
    });

    Object.keys(grouped).forEach((emoji) => {
      const badge = document.createElement("div");
      const isMyReaction = grouped[emoji].includes(currentUserId);
      badge.className = `reaction-badge ${isMyReaction ? "active" : ""}`;
      const emojiSpan = document.createElement("span");
      emojiSpan.textContent = emoji;
      badge.appendChild(emojiSpan);

      const countSpan = document.createElement("span");
      countSpan.className = "reaction-count";
      countSpan.textContent = grouped[emoji].length;
      badge.appendChild(countSpan);
      badge.onclick = () => toggleReaction(m.id, emoji);
      reactContainer.appendChild(badge);
    });
    info.appendChild(reactContainer);
  }

  group.appendChild(info);

  wrapper.appendChild(group);

  // Children Container
  if (m.children.length > 0) {
    const childrenDiv = document.createElement("div");
    childrenDiv.className = "message-children";
    childrenDiv.style.marginLeft = "20px"; // Indent
    childrenDiv.style.marginTop = "8px";
    childrenDiv.style.paddingLeft = "10px";
    childrenDiv.style.borderLeft = "2px solid var(--border-color)";

    m.children.forEach((child) => renderMessageNode(child, childrenDiv));
    wrapper.appendChild(childrenDiv);
  }

  parentContainer.appendChild(wrapper);
}

function handleInputKey(e) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
  // Auto-resize textarea
  const el = e.target;
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
  if (el.value === "") el.style.height = "auto";
}

async function sendMessage() {
  const content = msgInput.value.trim();

  if (!content && !fileToUpload) {
    return;
  }

  const timer = document.getElementById("self-destruct-timer");
  const expiresSec = timer ? timer.value : 0;

  const body = new FormData();
  if (isGroupMode) {
    body.append("group_thread_id", currentGroupThreadId);
  } else {
    body.append("thread_id", currentThreadId);
  }
  body.append("content", content);
  body.append("csrf_token", csrfToken);
  if (replyToId) body.append("reply_to_id", replyToId);
  if (expiresSec > 0) body.append("expires_in", expiresSec);
  if (fileToUpload) body.append("attachment", fileToUpload);

  const result = await api("send_message", "POST", body);

  if (result.error) {
    alert(
      "メッセージの送信に失敗しました: " +
        result.error +
        (result.details ? "\n" + result.details : ""),
    );
    return;
  }

  // Clear UI
  msgInput.value = "";
  msgInput.style.height = "auto";
  cancelReply();
  cancelUpload();

  if (isGroupMode) await loadGroupMessages();
  else await loadMessages();
}

async function deleteMessage(id) {
  if (!confirm("本当にこのメッセージを削除しますか？")) return;
  const body = new FormData();
  body.append("message_id", id);
  await api("delete_message", "POST", body);
  if (isGroupMode) loadGroupMessages();
  else loadMessages();
}

// --- Reply Logic ---
function startReply(id, username, content = "") {
  replyToId = id;
  document.getElementById("reply-target-name").innerText = username;
  const preview = document.getElementById("reply-preview-text");
  if (preview) {
    preview.innerText =
      content.substring(0, 50) + (content.length > 50 ? "..." : "");
  }
  replyBar.classList.add("active");
  msgInput.focus();
}

function cancelReply() {
  replyToId = null;
  replyBar.classList.remove("active");
}

// --- Drag & Drop Logic ---
const chatArea = document.querySelector(".chat-area");
const dropOverlay = document.querySelector(".drag-overlay");

["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
  chatArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

chatArea.addEventListener("dragenter", () =>
  chatArea.classList.add("drag-active"),
);
chatArea.addEventListener("dragleave", (e) => {
  if (e.target === dropOverlay) chatArea.classList.remove("drag-active");
});

chatArea.addEventListener("drop", (e) => {
  chatArea.classList.remove("drag-active");
  const dt = e.dataTransfer;
  const files = dt.files;
  if (files.length > 0) handleMediaUploadFiles(files); // Changed to handleMediaUploadFiles
});

let modalFileToUpload = null;

function openMediaUploadModal() {
  modalFileToUpload = null;
  const fileInput = document.getElementById("modal-file-input");
  const contentInput = document.getElementById("modal-content-input");
  if (fileInput) fileInput.value = "";
  if (contentInput) contentInput.value = "";

  const previewContainer = document.getElementById(
    "media-upload-preview-container",
  );
  if (previewContainer) {
    previewContainer.textContent = "";
    const placeholder = document.createElement("div");
    placeholder.className = "upload-placeholder";
    const placeholderIcon = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "svg",
    );
    placeholderIcon.setAttribute("width", "48");
    placeholderIcon.setAttribute("height", "48");
    placeholderIcon.setAttribute("viewBox", "0 0 24 24");
    placeholderIcon.setAttribute("fill", "none");
    placeholderIcon.setAttribute("stroke", "currentColor");
    placeholderIcon.setAttribute("stroke-width", "2");
    placeholderIcon.setAttribute("stroke-linecap", "round");
    placeholderIcon.setAttribute("stroke-linejoin", "round");
    placeholderIcon.style.color = "var(--text-secondary)";
    placeholderIcon.style.marginBottom = "15px";

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", "M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4");
    const polyline = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polyline",
    );
    polyline.setAttribute("points", "17 8 12 3 7 8");
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.setAttribute("x1", "12");
    line.setAttribute("y1", "3");
    line.setAttribute("x2", "12");
    line.setAttribute("y2", "15");

    placeholderIcon.appendChild(path);
    placeholderIcon.appendChild(polyline);
    placeholderIcon.appendChild(line);

    const placeholderText = document.createElement("p");
    placeholderText.style.margin = "0";
    placeholderText.style.color = "var(--text-secondary)";
    placeholderText.textContent = "クリックまたはドラッグ＆ドロップで選択";

    placeholder.appendChild(placeholderIcon);
    placeholder.appendChild(placeholderText);
    previewContainer.appendChild(placeholder);
  }

  const modal = document.getElementById("media-upload-modal");
  if (modal) modal.showModal();
}

function closeMediaUploadModal() {
  document.getElementById("media-upload-modal").close();
  modalFileToUpload = null;
}

function handleMediaUploadFiles(files) {
  if (files.length === 0) return;
  modalFileToUpload = files[0];
  const container = document.getElementById("media-upload-preview-container");
  container.textContent = "";

  if (modalFileToUpload.type.startsWith("image/")) {
    const reader = new FileReader();
    reader.readAsDataURL(modalFileToUpload);
    reader.onloadend = () => {
      const img = document.createElement("img");
      img.src = reader.result;
      img.style.maxWidth = "100%";
      img.style.maxHeight = "300px";
      img.style.borderRadius = "8px";
      img.style.objectFit = "contain";
      container.appendChild(img);
    };
  } else if (modalFileToUpload.type.startsWith("audio/")) {
    const div = document.createElement("div");
    div.className = "media-file-info";
    div.style.textAlign = "center";
    div.style.padding = "20px";

    const icon = document.createElement("span");
    icon.style.fontSize = "3rem";
    icon.textContent = "🎵";
    div.appendChild(icon);

    const name = document.createElement("p");
    name.style.marginTop = "10px";
    name.textContent = modalFileToUpload.name;
    div.appendChild(name);

    container.appendChild(div);
  } else if (modalFileToUpload.type.startsWith("video/")) {
    const video = document.createElement("video");
    video.src = URL.createObjectURL(modalFileToUpload);
    video.style.maxWidth = "100%";
    video.style.maxHeight = "300px";
    video.style.borderRadius = "8px";
    video.muted = true;
    video.autoplay = true;
    video.loop = true;
    container.appendChild(video);
  } else {
    const div = document.createElement("div");
    div.className = "media-file-info";
    div.style.textAlign = "center";
    div.style.padding = "20px";

    const icon = document.createElement("span");
    icon.style.fontSize = "3rem";
    icon.textContent = "📄";
    div.appendChild(icon);

    const name = document.createElement("p");
    name.style.marginTop = "10px";
    name.textContent = modalFileToUpload.name;
    div.appendChild(name);

    container.appendChild(div);
  }
}

async function submitMediaUpload() {
  if (!modalFileToUpload) {
    alert("ファイルを選択してください");
    return;
  }

  const content = document.getElementById("modal-content-input").value.trim();
  const body = new FormData();
  body.append("content", content);
  body.append("attachment", modalFileToUpload);

  let result;
  if (isDmMode) {
    if (!currentPartnerId) return;
    body.append("receiver_id", currentPartnerId);
    result = await api("send_direct_message", "POST", body);
  } else {
    if (!currentThreadId) return;
    body.append("thread_id", currentThreadId);
    if (replyToId) body.append("reply_to_id", replyToId);
    result = await api("send_message", "POST", body);
  }

  if (result.error) {
    alert("送信に失敗しました: " + result.error);
  } else {
    closeMediaUploadModal();
    if (isDmMode) {
      await loadDms();
      await loadDmPartners();
    } else {
      await loadMessages();
      cancelReply();
    }
  }
}

// Drag and drop for modal
document.addEventListener("DOMContentLoaded", () => {
  const dropzone = document.getElementById("media-upload-dropzone");
  if (dropzone) {
    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      dropzone.addEventListener(
        eventName,
        (e) => {
          e.preventDefault();
          e.stopPropagation();
        },
        false,
      );
    });
    dropzone.addEventListener("dragover", () =>
      dropzone.classList.add("drag-active"),
    );
    dropzone.addEventListener("dragleave", () =>
      dropzone.classList.remove("drag-active"),
    );
    dropzone.addEventListener("drop", (e) => {
      dropzone.classList.remove("drag-active");
      handleMediaUploadFiles(e.dataTransfer.files);
    });
  }
});

function cancelUpload() {
  fileToUpload = null;
  uploadPreview.classList.remove("active");
  previewContent.textContent = ""; // Clear safely
}

async function createThread() {
  const input = document.getElementById("new-thread-name");
  const catInput = document.getElementById("new-thread-category");
  const name = input.value.trim();
  const category = catInput ? catInput.value.trim() : "General";

  if (!name) return;
  const body = new FormData();
  body.append("name", name);
  body.append("category", category || "General");
  const result = await api("create_thread", "POST", body);

  if (result.error) {
    alert("スレッドの作成に失敗しました: " + result.error);
    return;
  }

  if (catInput) catInput.value = "";
  input.value = "";
  await loadThreads();
  hideCreateThread();

  // Switch to the newly created thread
  if (result.id) {
    switchThread(result.id, name, currentUserId, null, category || "General");
  }
}

function toggleThreadBrowser() {
  const browser = document.getElementById("thread-browser");
  browser.classList.toggle("active");
}

function toggleSidebar() {
  const sidebar = document.getElementById("main-sidebar");
  sidebar.classList.toggle("active");
  document.body.classList.toggle("sidebar-open");
}

function toggleSidebarCollapse() {
  const sidebar = document.getElementById("main-sidebar");
  sidebar.classList.toggle("collapsed");

  // オプション: 折りたたみ状態をLocalStorage等に保存することも検討可能
}

document.querySelectorAll(".nav-item").forEach((item) => {
  item.addEventListener("click", () => {
    const tabId = item.getAttribute("data-tab");
    document
      .querySelectorAll(".nav-item")
      .forEach((i) => i.classList.remove("active"));
    item.classList.add("active");
    document.querySelectorAll(".content-pane").forEach((p) => {
      p.classList.remove("active");
      p.style.display = "none"; // Ensure hide
    });
    const target = document.getElementById(tabId + "-pane");
    target.classList.add("active");
    target.style.display = "flex"; // Use Flex for layouts

    if (tabId === "dm") {
      isDmMode = true;
      document.getElementById("thread-browser").classList.remove("active"); // CSS based toggle
      backToHub();
    } else if (tabId === "threads") {
      isDmMode = false;
      document.getElementById("thread-browser").classList.add("active"); // CSS based toggle
    } else if (tabId === "favorites") {
      isDmMode = false;
      loadFavorites();
    } else if (tabId === "tactical-map") {
      isDmMode = false;
      initTacticalMap();
    }

    // モバイル表示でサイドバーが開いている場合は閉じる
    const sidebar = document.getElementById("main-sidebar");
    if (sidebar.classList.contains("active")) {
      toggleSidebar();
    }
  });
});

// --- Favorites Logic ---
async function toggleFavorite() {
  const body = new FormData();
  body.append("thread_id", currentThreadId);
  const res = await api("toggle_favorite", "POST", body);
  if (res.success) {
    updateFavoriteIcon(res.status === "added");
    if (
      document
        .querySelector('.nav-item[data-tab="favorites"]')
        .classList.contains("active")
    ) {
      loadFavorites();
    }
  }
}

async function checkFavoriteStatus() {
  const res = await api(`check_favorite&thread_id=${currentThreadId}`);
  updateFavoriteIcon(res.is_favorite);
}

function updateFavoriteIcon(isFav) {
  const btn = document.getElementById("fav-btn");
  if (isFav) {
    btn.innerText = "★";
    btn.style.color = "gold";
  } else {
    btn.innerText = "☆";
    btn.style.color = "var(--text-secondary)";
  }
}

function setTheme(mode) {
  if (mode === "light") {
    document.body.classList.add("light-theme");
  } else {
    document.body.classList.remove("light-theme");
  }
}

function updateAccentColor(color) {
  document.documentElement.style.setProperty("--accent-color", color);
  // Also update hover color (lighter version)
  const r = parseInt(color.slice(1, 3), 16);
  const g = parseInt(color.slice(3, 5), 16);
  const b = parseInt(color.slice(5, 7), 16);
  const hoverColor = `rgba(${r}, ${g}, ${b}, 0.8)`;
  document.documentElement.style.setProperty("--accent-hover", hoverColor);
}

async function loadFavorites() {
  const threads = await api("get_favorites");
  const list = document.getElementById("fav-thread-list");
  list.innerText = "";
  if (threads.length === 0) {
    const d = document.createElement("div");
    d.style.padding = "1rem";
    d.style.color = "var(--text-secondary)";
    d.style.fontSize = "0.85rem";
    d.innerText =
      "お気に入りスレッドがありません。\nスレッドタイトルの☆を押して追加できます。";
    list.appendChild(d);
    return;
  }
  threads.forEach((t) => {
    const item = document.createElement("div");
    item.className = `thread-item ${t.id == currentThreadId ? "active" : ""}`;
    item.textContent = "# " + t.name;
    item.onclick = () => {
      // Switch to Threads tab context implicitly but keep view?
      // Better UX: Switch to Threads tab and load this thread.
      document.querySelector('.nav-item[data-tab="threads"]').click();
      switchThread(t.id, t.name, t.creator_id);
    };
    list.appendChild(item);
  });
}

// --- Discord-like Friend & DM Logic ---

function backToHub() {
  currentPartnerId = null;
  const hub = document.getElementById("dm-hub-view");
  const chat = document.getElementById("dm-chat-view");
  if (hub && chat) {
    hub.style.display = "flex";
    chat.style.display = "none";
    loadHubFriends();
  }
}

function switchToDmChat(id, name, avatarUrl = null, status = "online") {
  console.log("[DM] Switching to DM chat with:", id, name);
  currentPartnerId = id;
  document.getElementById("dm-hub-view").style.display = "none";
  document.getElementById("dm-chat-view").style.display = "flex";

  const infoContainer = document.getElementById("current-dm-partner-info");
  infoContainer.textContent = "";
  infoContainer.style.display = "flex";
  infoContainer.style.alignItems = "center";
  infoContainer.style.gap = "10px";

  infoContainer.appendChild(getAvatarElement(name, status, avatarUrl));
  const nameH3 = document.createElement("h3");
  nameH3.className = "thread-name";
  nameH3.id = "current-dm-partner-name";
  nameH3.innerText = name;
  infoContainer.appendChild(nameH3);

  const container = document.getElementById("dm-message-container");
  container.innerText = "";
  container.appendChild(getSkeletonLoader());
  isDmMode = true;
  isGroupMode = false;
  currentGroupThreadId = null;
  loadDms(1000);
  updateMuteIcon();
}

async function loadHubFriends() {
  const friends = await api("get_friends");
  const list = document.getElementById("hub-friend-list");
  list.textContent = "";
  if (friends.length === 0) {
    const emptyMsg = document.createElement("div");
    emptyMsg.style.padding = "10px";
    emptyMsg.style.color = "gray";
    emptyMsg.textContent = "まだフレンドがいません";
    list.appendChild(emptyMsg);
    return;
  }
  friends.forEach((f) => {
    const d = document.createElement("div");
    d.className = "thread-item";
    d.style.display = "flex";
    d.style.justifyContent = "space-between";
    d.style.alignItems = "center";
    d.style.cursor = "pointer";

    const leftSide = document.createElement("div");
    leftSide.style.display = "flex";
    leftSide.style.alignItems = "center";
    leftSide.style.gap = "10px";
    leftSide.appendChild(
      getAvatarElement(f.username, f.status || "offline", f.avatar_url),
    );

    const nameSpan = document.createElement("span");
    nameSpan.textContent = f.username;
    leftSide.appendChild(nameSpan);
    d.appendChild(leftSide);

    const timeSpan = document.createElement("span");
    timeSpan.style.fontSize = "0.8rem";
    timeSpan.style.color = "var(--text-secondary)";
    timeSpan.textContent = f.last_msg_at
      ? new Date(f.last_msg_at).toLocaleString()
      : "会話なし";
    d.appendChild(timeSpan);

    d.onclick = () => switchToDmChat(f.id, f.username, f.avatar_url, f.status);
    list.appendChild(d);
  });
}

// --- Modal Logic ---
function showAddFriendModal() {
  document.getElementById("add-friend-modal").showModal();
  document.getElementById("user-search-results").textContent = "";
  document.getElementById("user-search-input").value = "";
}

async function searchUsers() {
  const q = document.getElementById("user-search-input").value;
  if (!q) return;
  const res = await api(`search_users&q=${encodeURIComponent(q)}`);
  const list = document.getElementById("user-search-results");
  list.textContent = "";
  if (res.length === 0) {
    list.innerText = "見つかりませんでした";
    return;
  }
  res.forEach((u) => {
    const d = document.createElement("div");
    d.className = "thread-item";
    d.style.display = "flex";
    d.style.justifyContent = "space-between";
    d.style.alignItems = "center";
    d.style.gap = "10px";

    const userPart = document.createElement("div");
    userPart.style.display = "flex";
    userPart.style.alignItems = "center";
    userPart.style.gap = "10px";
    userPart.appendChild(getAvatarElement(u.username, u.status, u.avatar_url));
    const nameSpan = document.createElement("span");
    nameSpan.textContent = `${u.username}(ID: ${u.id})`;
    userPart.appendChild(nameSpan);
    d.appendChild(userPart);

    const btn = document.createElement("button");
    btn.innerText = "申請";
    btn.className = "btn-primary";
    btn.style.padding = "10px 15px";
    btn.style.fontSize = "1.0rem";
    btn.onclick = async () => {
      if (confirm(`ID: ${u.id} ${u.username} に申請を送りますか`)) {
        const body = new FormData();
        body.append("target_id", u.id);
        const r = await api("request_friend", "POST", body);
        if (r.success) alert("送信しました");
        else alert(r.error);
      }
    };
    d.appendChild(btn);
    list.appendChild(d);
  });
}

function showPendingRequestsModal() {
  document.getElementById("pending-requests-modal").showModal();
  loadPendingRequests();
}

async function loadPendingRequests() {
  const reqs = await api("get_friend_requests");
  const list = document.getElementById("pending-requests-list-modal");
  list.textContent = "";
  if (reqs.length === 0) list.innerText = "承認待ちのリクエストはありません";
  reqs.forEach((r) => {
    const d = document.createElement("div");
    d.className = "thread-item";
    d.style.display = "flex";
    d.style.justifyContent = "space-between";
    const nameSpan = document.createElement("span");
    nameSpan.textContent = r.username;
    d.appendChild(nameSpan);
    const btn = document.createElement("button");
    btn.innerText = "承認";
    btn.className = "btn-primary";
    btn.onclick = async () => {
      const body = new FormData();
      body.append("request_id", r.id);
      await api("accept_friend", "POST", body);
      loadPendingRequests();
      loadHubFriends();
    };
    d.appendChild(btn);
    list.appendChild(d);
  });
}

function showBlockedModal() {
  document.getElementById("blocked-users-modal").showModal();
  loadBlockedUsers();
}

async function loadBlockedUsers() {
  const users = await api("get_blocked_users");
  const list = document.getElementById("blocked-users-list");
  list.textContent = "";
  if (users.length === 0) list.innerText = "ブロックしているユーザーはいません";
  users.forEach((u) => {
    const d = document.createElement("div");
    d.className = "thread-item";
    d.style.display = "flex";
    d.style.justifyContent = "space-between";
    const nameSpan = document.createElement("span");
    nameSpan.textContent = u.username;
    d.appendChild(nameSpan);
    const btn = document.createElement("button");
    btn.innerText = "解除";
    btn.className = "btn-secondary";
    btn.onclick = async () => {
      const body = new FormData();
      body.append("target_id", u.id);
      await api("unblock_user", "POST", body);
      loadBlockedUsers();
    };
    d.appendChild(btn);
    list.appendChild(d);
  });
}

async function blockCurrentPartner() {
  if (!currentPartnerId) return;
  if (confirm("このユーザーをブロックしますか？\nフレンドも解除されます。")) {
    const body = new FormData();
    body.append("target_id", currentPartnerId);
    await api("block_user", "POST", body);
    backToHub();
  }
}

// Fallback for partner-list references (if any left) can be ignored as we utilize hub-friend-list
async function loadDmPartners() {
  // Alias to hub loader if called from polling
  loadHubFriends();
}

async function loadDms(minDelay = 0) {
  if (!currentPartnerId) return;
  const startTime = Date.now();
  const dms = await api(`get_direct_messages&partner_id=${currentPartnerId}`);

  if (minDelay > 0) {
    const elapsed = Date.now() - startTime;
    const remaining = minDelay - elapsed;
    if (remaining > 0) await new Promise((r) => setTimeout(r, remaining));
  }

  // Mark as read
  const markBody = new FormData();
  markBody.append("partner_id", currentPartnerId);
  api("mark_dms_as_read", "POST", markBody);

  const container = document.getElementById("dm-message-container");
  const isAtBottom =
    container.scrollHeight - container.scrollTop <=
    container.clientHeight + 100;
  container.innerText = "";

  if (dms.error) {
    alert("DMの読み込みに失敗しました: " + dms.error);
    return;
  }
  dms.forEach &&
    dms.forEach((m) => {
      const group = document.createElement("div");
      group.className = "message-group";
      group.appendChild(getAvatarElement(m.username, "online", m.avatar_url));

      const info = document.createElement("div");
      info.className = "message-info";

      const header = document.createElement("div");
      header.className = "message-header";

      const user = document.createElement("span");
      user.className = "message-user clickable-username";
      user.textContent = m.username;
      user.style.cursor = "pointer";
      user.onclick = (e) => {
        e.stopPropagation();
        showUserProfile(m.sender_id, m.username);
      };

      const time = document.createElement("span");
      time.className = "message-time";
      time.textContent = m.created_at;

      if (m.username === currentUserName && m.is_read == 1) {
        const readLabel = document.createElement("span");
        readLabel.style.fontSize = "0.7rem";
        readLabel.style.color = "var(--accent-color)";
        readLabel.style.marginLeft = "8px";
        readLabel.innerText = "既読";
        time.appendChild(readLabel);
      }

      header.appendChild(user);
      header.appendChild(time);

      const contentDiv = document.createElement("div");
      contentDiv.className = "message-content";

      // Render Rich Text / Markdown
      contentDiv.replaceChildren(formatMessage(m.content || ""));
      applyHighlighting(contentDiv);

      if (m.attachment_path) {
        const ext = m.attachment_path.split(".").pop().toLowerCase();
        const isImage = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(
          ext,
        );
        const isAudio = ["mp3", "wav", "ogg"].includes(ext);
        const isVideo = ["mp4", "webm", "ogv", "mov", "avi"].includes(ext);

        if (isImage) {
          const img = document.createElement("img");
          img.src = m.attachment_path;
          img.className = "preview-img";
          img.style.display = "block";
          img.style.marginTop = "10px";
          img.onclick = () => window.open(m.attachment_path, "_blank");
          contentDiv.appendChild(img);
        } else if (isAudio) {
          const audio = document.createElement("audio");
          audio.src = m.attachment_path;
          audio.controls = true;
          audio.style.display = "block";
          audio.style.marginTop = "10px";
          audio.style.maxWidth = "100%";
          contentDiv.appendChild(audio);
        } else if (isVideo) {
          const video = document.createElement("video");
          video.src = m.attachment_path;
          video.controls = true;
          video.style.display = "block";
          video.style.marginTop = "10px";
          video.style.maxWidth = "100%";
          contentDiv.appendChild(video);
        }

        const dlLink = document.createElement("a");
        const fileName = m.attachment_path.split("/").pop();
        dlLink.href = "download.php?file=" + fileName;
        dlLink.target = "_blank";
        dlLink.innerText = "⬇️ ダウンロード";
        dlLink.style.display = "inline-block";
        dlLink.style.fontSize = "0.75rem";
        dlLink.style.marginTop = "5px";
        dlLink.style.color = "var(--accent-color)";
        contentDiv.appendChild(dlLink);
      }

      info.appendChild(header);
      info.appendChild(contentDiv);

      if (m.is_edited == 1) {
        const editedLabel = document.createElement("span");
        editedLabel.style.fontSize = "0.7rem";
        editedLabel.style.opacity = "0.5";
        editedLabel.style.marginLeft = "5px";
        editedLabel.innerText = "(編集済み)";
        contentDiv.appendChild(editedLabel);
      }

      if (m.sender_id == currentUserId) {
        const editBtn = document.createElement("button");
        editBtn.className = "msg-action-btn";
        editBtn.style.position = "absolute";
        editBtn.style.right = "10px";
        editBtn.style.top = "10px";
        editBtn.textContent = "";
        const editImg = document.createElement("img");
        editImg.src = "assets/img/edit.svg";
        editImg.alt = "編集";
        editImg.style.width = "16px";
        editImg.style.height = "16px";
        editBtn.appendChild(editImg);
        editBtn.onclick = () => startEditMessage(m, true);
        group.appendChild(editBtn);
      }

      group.appendChild(info);
      container.appendChild(group);
    });

  if (dms.length > 0) {
    const latest = dms[dms.length - 1];
    if (
      lastDmId !== 0 &&
      latest.id > lastDmId &&
      latest.sender_id != currentUserId
    ) {
      // Browser notification is now handled via Socket.io for real-time response
      // sendNotification(`新着DM: ${latest.username}`, latest.content, 'dm', currentPartnerId);
    }
    lastDmId = latest.id;
  }

  if (isAtBottom) container.scrollTop = container.scrollHeight;
}

async function showUserPicker() {
  const modal = document.getElementById("user-picker-modal");
  const list = document.getElementById("all-user-list");
  list.innerText = "Loading...";
  modal.showModal();

  const users = await api("get_all_users");
  list.innerText = "";
  users.forEach((u) => {
    const d = document.createElement("div");
    d.style.padding = "8px";
    d.style.cursor = "pointer";
    d.className = "thread-item";
    d.style.display = "flex";
    d.style.alignItems = "center";
    d.style.gap = "10px";
    d.appendChild(getAvatarElement(u.username, u.status, u.avatar_url));
    const nameSpan = document.createElement("span");
    nameSpan.innerText = u.username;
    d.appendChild(nameSpan);

    d.onclick = async () => {
      if (confirm(u.username + " にフレンドリクエストを送信しますか？")) {
        const body = new FormData();
        body.append("target_id", u.id);
        const res = await api("request_friend", "POST", body);
        if (res.success) {
          alert("送信しました");
          modal.close();
        } else {
          alert(res.error || "エラーが発生しました");
        }
      }
    };
    list.appendChild(d);
  });
}

async function sendDm() {
  const input = document.getElementById("dm-msg-input");
  const content = input.value.trim();
  if (!currentPartnerId) {
    alert("DMの送信先(パートナー)が選択されていません。");
    return;
  }
  if (!content && !dmFileToUpload) return;
  console.log("[DM] Sending DM to:", currentPartnerId, "Content:", content);

  const timer = document.getElementById("dm-self-destruct-timer");
  const expiresSec = timer ? timer.value : 0;

  const body = new FormData();
  body.append("receiver_id", currentPartnerId);
  body.append("content", content);
  if (expiresSec > 0) body.append("expires_in", expiresSec);
  if (dmFileToUpload) body.append("attachment", dmFileToUpload);

  const result = await api("send_direct_message", "POST", body);

  if (result.error) {
    alert("DMの送信に失敗しました: " + result.error);
    return;
  }

  input.value = "";
  input.style.height = "auto";
  cancelDmUpload();
  await loadDms();
  await loadDmPartners(); // Refresh logic to put recent at top if sorted
}

function handleDmInputKey(e) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendDm();
  }
}

// Reusing drag drop logic for DM logic (simplified)
const dmChatArea = document.getElementById("dm-chat-area");
if (dmChatArea) {
  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    dmChatArea.addEventListener(
      eventName,
      (e) => {
        e.preventDefault();
        e.stopPropagation();
      },
      false,
    );
  });
  dmChatArea.addEventListener("drop", (e) => {
    const dt = e.dataTransfer;
    if (dt.files.length > 0) {
      dmFileToUpload = dt.files[0];
      const pv = document.getElementById("dm-preview-content");
      pv.innerText = "📄 " + dmFileToUpload.name;
      document.getElementById("dm-upload-preview").classList.add("active");
    }
  });
}

function cancelDmUpload() {
  dmFileToUpload = null;
  document.getElementById("dm-upload-preview").classList.remove("active");
}

function showCreateThread() {
  document.getElementById("create-thread-area").classList.add("active");
  document.getElementById("create-thread-toggle-container").style.display =
    "none";
  document.getElementById("new-thread-name").focus();
}

function hideCreateThread() {
  document.getElementById("create-thread-area").classList.remove("active");
  document.getElementById("create-thread-toggle-container").style.display =
    "block";
  document.getElementById("new-thread-name").value = "";
}

// Realtime with Socket.io
let socket = null;

function initRealtime() {
  if (typeof io === "undefined") return;
  const hostname = window.location.hostname || "localhost";
  socket = io(`http://${hostname}:3000`);

  socket.on("connect", () => {
    console.log("Connected to realtime server");
    socket.emit("register", currentUserId);
    if (currentThreadId) socket.emit("join_thread", currentThreadId);
  });

  socket.on("new_message", (msg) => {
    if (!isGroupMode && !isDmMode && currentThreadId == msg.thread_id) {
      loadMessages();
    }
    if (msg.user_id != currentUserId) {
      // Trigger browser notification
      sendNotification(
        `新着メッセージ: ${msg.username}`,
        msg.content,
        "thread",
        msg.thread_id,
      );
    }
  });

  socket.on("new_group_message", (msg) => {
    if (isGroupMode && currentGroupThreadId == msg.group_thread_id) {
      loadGroupMessages();
    }
    if (msg.user_id != currentUserId) {
      // Trigger browser notification
      sendNotification(
        `新着グループメッセージ: ${msg.username}`,
        msg.content,
        "group",
        msg.group_thread_id,
      );
    }
  });

  socket.on("new_dm", (msg) => {
    if (isDmMode && currentPartnerId == msg.sender_id) {
      loadDms();
    }
    if (msg.sender_id != currentUserId) {
      // Trigger browser notification
      sendNotification(
        `新着DM: ${msg.username}`,
        msg.content,
        "dm",
        msg.sender_id,
      );
      loadDmPartners();
    }
  });

  socket.on("typing_status", (data) => {
    const indicator = document.getElementById(
      isDmMode ? "dm-typing-indicator" : "typing-indicator",
    );
    if (indicator) {
      if (data.isTyping) {
        indicator.innerText = `${data.username} が入力中...`;
        indicator.style.visibility = "visible";
      } else {
        indicator.style.visibility = "hidden";
      }
    }
  });

  socket.on("new_notification", () => {
    loadNotifications();
  });
}

// Push Notifications
async function initPush() {
  if (!("serviceWorker" in navigator) || !("PushManager" in window)) return;

  const registration = await navigator.serviceWorker.ready;
  let subscription = await registration.pushManager.getSubscription();

  if (!subscription) {
    const publicKey = window.SYCS_CONFIG.vapidPublicKey;
    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: publicKey,
    });

    // Save to backend
    await fetch("index.php?api=push_subscribe", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        csrf_token: window.SYCS_CONFIG.csrfToken,
        ...subscription.toJSON(),
      }),
    });
  }
}

async function selectThread(id, name) {
  currentThreadId = id;
  const title = document.getElementById("thread-title");
  if (title) title.innerText = "#" + name;

  const container = document.getElementById("message-container");
  if (container) {
    container.innerText = "";
    container.appendChild(getSkeletonLoader());
  }

  if (socket) {
    socket.emit("join_thread", id);
  }

  await loadMessages(500);
  updateThreadActions();
  api(`set_last_thread&thread_id=${id}`);
}

document.addEventListener("DOMContentLoaded", () => {
  // Initial Load
  loadThreads();
  loadGroupThreads();
  loadMuteStatuses();
  loadNotifications();
  initRealtime();
  initPush();
  // GPS 位置情報取得の初期化
  if (typeof locationManager !== "undefined") {
    locationManager.init("gps-status-display", 30000);
  }

  if (isDmMode && currentPartnerId) {
    const container = document.getElementById("dm-message-container");
    if (container) {
      container.innerText = "";
      container.appendChild(getSkeletonLoader());
    }
    loadDms(1000);
  } else if (!isDmMode && currentThreadId) {
    const container = document.getElementById("message-container");
    if (container) {
      container.innerText = "";
      container.appendChild(getSkeletonLoader());
    }
    loadMessages(1000);
  }
  // Also update thread actions logic initially
  updateThreadActions();

  // Notifications
  if (Notification.permission === "default") {
    Notification.requestPermission();
  }

  // 新機能: オンラインユーザーリストの初期ロードと定期更新 (15秒おき)
  loadOnlineUsers();
  setInterval(loadOnlineUsers, 15000);

  // 新機能: DM未読バッジの初期ロードと定期更新 (10秒おき)
  updateUnreadDmBadge();
  setInterval(updateUnreadDmBadge, 10000);

  // 通知をすべて既読にするボタンのイベントリスナー
  const markAllNotifBtn = document.getElementById("mark-all-read-btn");
  if (markAllNotifBtn) {
    markAllNotifBtn.removeAttribute("onclick");
    markAllNotifBtn.addEventListener("click", markAllNotificationsRead);
  }

  // Polling (Reduced/Removed except for status)
  setInterval(() => {
    // We keep status update as it's not strictly real-time message dependent
    // fetchTypingUsers(); // Replaced by Socket.io
  }, 5000);
});

async function loadMuteStatuses() {
  const res = await api("get_mute_statuses");
  mutedTargets = new Set(res.map((m) => `${m.target_type}:${m.target_id}`));
  updateMuteIcon();
}

async function toggleMute() {
  const targetType = isDmMode ? "dm" : isGroupMode ? "group" : "thread";
  const targetId = isDmMode
    ? currentPartnerId
    : isGroupMode
      ? currentGroupThreadId
      : currentThreadId;
  if (!targetId) return;

  const key = `${targetType}:${targetId}`;
  const isCurrentlyMuted = mutedTargets.has(key);
  const res = await api("toggle_mute", "POST", {
    target_type: targetType,
    target_id: targetId,
    is_muted: isCurrentlyMuted ? "0" : "1",
  });

  if (res.success) {
    if (isCurrentlyMuted) mutedTargets.delete(key);
    else mutedTargets.add(key);
    updateMuteIcon();
  }
}

let notificationDropdownOpen = false;
async function toggleNotificationDropdown(e) {
  if (e) e.stopPropagation();
  const dropdown = document.getElementById("notification-dropdown");
  const overlay = document.getElementById("notification-overlay");
  notificationDropdownOpen = !notificationDropdownOpen;

  const displayMode = notificationDropdownOpen ? "flex" : "none";
  dropdown.style.display = displayMode;
  overlay.style.display = notificationDropdownOpen ? "block" : "none";

  if (notificationDropdownOpen) {
    loadNotifications();
  }
}

// Removed old click listener since we use an overlay for closing

async function loadNotifications() {
  const notifications = await api("get_notifications");
  const list = document.getElementById("notification-list");
  const badge = document.getElementById("notif-badge");

  list.innerHTML = "";
  let unreadCount = 0;

  if (notifications.length === 0) {
    list.innerHTML =
      '<div class="empty-state" style="padding:40px 20px;">通知はありません</div>';
  } else {
    notifications.forEach((n) => {
      if (!n.is_read) unreadCount++;
      item.className = `notif-item ${n.is_read ? "read" : "unread"}`;
      item.innerHTML = `
          <div class="content">${escapeHTML(n.content)}</div>
          <div class="time">${new Date(n.created_at).toLocaleString()}</div>
          ${!n.is_read ? '<div class="notif-unread-dot"></div>' : ""}
      `;

      item.onclick = async (e) => {
        e.stopPropagation();
        await api("mark_notification_read", "POST", {
          notification_id: n.id,
        });
        if (n.link) window.location.href = n.link;
        else loadNotifications();
      };
      list.appendChild(item);
    });
  }

  badge.textContent = unreadCount;
  badge.style.display = unreadCount > 0 ? "flex" : "none";
}

async function markAllNotificationsRead() {
  console.log("[Notifications] Marking all as read...");
  const res = await api("mark_all_notifications_read", "POST");
  if (res.success) {
    console.log("[Notifications] Successfully marked all as read.");
    loadNotifications();
  } else {
    console.error("[Notifications] Failed to mark all as read:", res);
    alert("通知の書き換えに失敗しました: " + (res.error || "Unknown error"));
  }
}
window.markAllNotificationsRead = markAllNotificationsRead;

function updateMuteIcon() {
  const btn = document.getElementById("mute-btn");
  if (!btn) return;
  const targetType = isDmMode ? "dm" : isGroupMode ? "group" : "thread";
  const targetId = isDmMode
    ? currentPartnerId
    : isGroupMode
      ? currentGroupThreadId
      : currentThreadId;
  const key = `${targetType}:${targetId}`;

  if (mutedTargets.has(key)) {
    btn.style.color = "#f87171";
    btn.title = "ミュート中";
  } else {
    btn.style.color = "var(--text-secondary)";
    btn.title = "通知をミュート";
  }
}

function sendNotification(title, body, targetType = "thread", targetId = 0) {
  const key = `${targetType}:${targetId}`;

  // Check keywords first (Keywords override mute)
  let isKeywordMatch = false;
  if (userKeywords.length > 0) {
    isKeywordMatch = userKeywords.some((k) => k && body.includes(k));
  }

  if (mutedTargets.has(key) && !isKeywordMatch) return;

  // Determine if we should show the notification
  const isDifferentThread =
    (targetType === "thread" && targetId != currentThreadId) ||
    (targetType === "group" && targetId != currentGroupThreadId) ||
    (targetType === "dm" && targetId != currentPartnerId);

  // Show if window is NOT focused OR if it's from a different thread (even if focused, to be helpful)
  if (
    (!isWindowFocused || isDifferentThread) &&
    Notification.permission === "granted"
  ) {
    new Notification(title, {
      body,
      icon: "SYCS_favicon.svg",
    });
  }
}

async function toggleReaction(messageId, emoji) {
  const body = new FormData();
  body.append("message_id", messageId);
  body.append("emoji", emoji);
  const res = await api("toggle_reaction", "POST", body);
  if (res.error) {
    alert("リアクションに失敗しました");
  } else {
    await loadMessages();
    const picker = document.querySelector(".emoji-picker-popover");
    if (picker) picker.remove();
  }
}

function showEmojiPicker(event, messageId) {
  event.stopPropagation();
  const existing = document.querySelector(".emoji-picker-popover");
  if (existing) existing.remove();

  const popover = document.createElement("div");
  popover.className = "emoji-picker-popover";

  const emojis = [
    "👍",
    "❤️",
    "😂",
    "😮",
    "😢",
    "🔥",
    "✅",
    "🚀",
    "👀",
    "✨",
    "💯",
    "🙏",
  ];
  emojis.forEach((emoji) => {
    const btn = document.createElement("button");
    btn.className = "emoji-btn";
    btn.innerText = emoji;
    btn.onclick = () => toggleReaction(messageId, emoji);
    popover.appendChild(btn);
  });

  document.body.appendChild(popover);

  const rect = event.target.getBoundingClientRect();
  popover.style.top =
    rect.top + window.scrollY - popover.offsetHeight - 10 + "px";
  popover.style.left = rect.left + window.scrollX + "px";

  const closePicker = (e) => {
    if (!popover.contains(e.target)) {
      popover.remove();
      document.removeEventListener("click", closePicker);
    }
  };
  setTimeout(() => document.addEventListener("click", closePicker), 10);
}

async function togglePin(messageId) {
  const body = new FormData();
  body.append("message_id", messageId);
  const res = await api("toggle_pin", "POST", body);
  if (res.error) {
    alert("ピン留めに失敗しました");
  } else {
    await loadMessages();
  }
}

// --- Search Logic ---
function toggleAdvancedSearch() {
  const panel = document.getElementById("advanced-search-panel");
  if (panel)
    panel.style.display = panel.style.display === "none" ? "block" : "none";
}

async function searchMessages() {
  const queryInput = document.getElementById("search-input");
  const keyword = queryInput ? queryInput.value.trim() : "";

  const hasAttachment = document.getElementById("search-has-attachment").checked
    ? "1"
    : "0";
  const dateFrom = document.getElementById("search-date-from").value;
  const dateTo = document.getElementById("search-date-to").value;

  if (!keyword && hasAttachment === "0" && !dateFrom && !dateTo) return;

  let url = `search_messages&keyword=${encodeURIComponent(keyword)}&has_attachment=${hasAttachment}`;
  if (dateFrom) url += `&date_from=${dateFrom}`;
  if (dateTo) url += `&date_to=${dateTo}`;

  if (isDmMode) url += `&partner_id=${currentPartnerId}`;
  else if (isGroupMode) url += `&group_thread_id=${currentGroupThreadId}`;
  else url += `&thread_id=${currentThreadId}`;

  const res = await api(url);
  const list = document.getElementById("search-results-list");
  const overlay = document.getElementById("search-results-overlay");

  list.textContent = "";
  overlay.style.display = "flex";

  if (res.length === 0) {
    const emptyMsg = document.createElement("div");
    emptyMsg.style.padding = "10px";
    emptyMsg.style.color = "var(--text-secondary)";
    emptyMsg.textContent = "結果が見つかりませんでした";
    list.appendChild(emptyMsg);
    return;
  }

  res.forEach((m) => {
    const div = document.createElement("div");
    div.className = "search-result-item";
    const userDiv = document.createElement("div");
    userDiv.style.cssText =
      "font-size:0.75rem; color:var(--accent-color); font-weight:700;";
    userDiv.textContent = m.username || "";

    const bodyDiv = document.createElement("div");
    bodyDiv.style.cssText = "font-size:0.85rem; margin:4px 0;";
    bodyDiv.className = "message-content"; // Add class for styling
    bodyDiv.replaceChildren(
      formatMessage(m.content || (m.attachment_path ? "[添付ファイル]" : "")),
    );
    applyHighlighting(bodyDiv);

    const timeDiv = document.createElement("div");
    timeDiv.style.cssText = "font-size:0.65rem; opacity:0.6;";
    timeDiv.textContent = m.created_at || "";

    div.appendChild(userDiv);
    div.appendChild(bodyDiv);
    div.appendChild(timeDiv);
    div.onclick = () => {
      const target = document.getElementById("message-" + m.id);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
        target.style.backgroundColor = "rgba(99, 102, 241, 0.2)";
        setTimeout(() => (target.style.backgroundColor = ""), 2000);
        toggleSearch(false);
      } else {
        alert("メッセージが現在の読み込み範囲外です");
      }
    };
    list.appendChild(div);
  });
}

function toggleSearch(show) {
  const overlay = document.getElementById("search-results-overlay");
  if (overlay) overlay.style.display = show ? "flex" : "none";
}

// ========== 新機能: ピン留めメッセージ一覧 ==========
async function showPinnedMessages() {
  const modal = document.getElementById("pinned-messages-modal");
  const list = document.getElementById("pinned-messages-list");
  list.textContent = "";
  const loadingMsg = document.createElement("div");
  loadingMsg.style.textAlign = "center";
  loadingMsg.style.color = "var(--text-secondary)";
  loadingMsg.style.padding = "40px 0";
  loadingMsg.textContent = "読み込み中...";
  list.appendChild(loadingMsg);
  modal.showModal();

  let url = "get_pinned_messages";
  if (isGroupMode && currentGroupThreadId)
    url += `&group_thread_id=${currentGroupThreadId}`;
  else if (currentThreadId) url += `&thread_id=${currentThreadId}`;
  else {
    list.textContent = "";
    const selectMsg = document.createElement("div");
    selectMsg.style.textAlign = "center";
    selectMsg.style.color = "var(--text-secondary)";
    selectMsg.style.padding = "40px 0";
    selectMsg.textContent = "スレッドを選択してください";
    list.appendChild(selectMsg);
    return;
    return;
  }

  const msgs = await api(url);
  list.textContent = "";

  if (!msgs || msgs.length === 0) {
    const noMsg = document.createElement("div");
    noMsg.style.textAlign = "center";
    noMsg.style.color = "var(--text-secondary)";
    noMsg.style.padding = "40px 0";
    noMsg.textContent = "ピン留めされたメッセージはありません";
    list.appendChild(noMsg);
    return;
  }

  msgs.forEach((m) => {
    const div = document.createElement("div");
    div.style.cssText =
      "border:1px solid var(--border-color); border-radius:8px; padding:12px; margin-bottom:10px; background:var(--bg-secondary); cursor:pointer; transition: background 0.15s;";
    div.onmouseenter = () => (div.style.background = "var(--card-bg)");
    div.onmouseleave = () => (div.style.background = "var(--bg-secondary)");

    const header = document.createElement("div");
    header.style.cssText =
      "display:flex; align-items:center; gap:8px; margin-bottom:6px;";
    header.appendChild(getAvatarElement(m.username, "online", m.avatar_url));

    const userSpan = document.createElement("span");
    userSpan.style.cssText = "font-weight:600; font-size:0.9rem;";
    userSpan.textContent = m.username || "";

    const dateSpan = document.createElement("span");
    dateSpan.style.cssText = "font-size:0.75rem; color:var(--text-secondary);";
    dateSpan.textContent = m.created_at || "";

    header.appendChild(userSpan);
    header.appendChild(dateSpan);

    const content = document.createElement("div");
    content.className = "message-content"; // Add class for styling
    content.style.cssText =
      "font-size:0.9rem; color:var(--text-primary); padding-left:4px; word-break:break-word;";
    content.replaceChildren(formatMessage(m.content || "[添付ファイル]"));
    applyHighlighting(content);

    const actions = document.createElement("div");
    actions.style.cssText = "display:flex; gap:8px; margin-top:10px;";

    const jumpBtn = document.createElement("button");
    jumpBtn.className = "btn-secondary";
    jumpBtn.style.cssText = "padding:4px 12px; font-size:0.8rem;";
    jumpBtn.innerText = "↗️ ジャンプ";
    jumpBtn.onclick = () => {
      modal.close();
      const target = document.getElementById("message-" + m.id);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
        target.style.backgroundColor = "rgba(99, 102, 241, 0.25)";
        setTimeout(() => (target.style.backgroundColor = ""), 2000);
      }
    };

    const unpinBtn = document.createElement("button");
    unpinBtn.className = "btn-secondary";
    unpinBtn.style.cssText =
      "padding:4px 12px; font-size:0.8rem; color:#f87171;";
    unpinBtn.innerText = "📌 解除";
    unpinBtn.onclick = async () => {
      const body = new FormData();
      body.append("message_id", m.id);
      await api("toggle_pin", "POST", body);
      showPinnedMessages();
      if (!isDmMode && !isGroupMode) loadMessages();
    };

    actions.appendChild(jumpBtn);
    actions.appendChild(unpinBtn);

    div.appendChild(header);
    div.appendChild(content);
    div.appendChild(actions);
    list.appendChild(div);
  });
}

// ========== 新機能: オンラインユーザーリスト ==========
let onlineUsersCollapsed = false;

function toggleOnlineUsers() {
  onlineUsersCollapsed = !onlineUsersCollapsed;
  const list = document.getElementById("online-users-list");
  const icon = document.getElementById("online-users-toggle-icon");
  if (list) list.style.display = onlineUsersCollapsed ? "none" : "block";
  if (icon) icon.innerText = onlineUsersCollapsed ? "▸" : "▾";
}

async function loadOnlineUsers() {
  if (onlineUsersCollapsed) return;
  const list = document.getElementById("online-users-list");
  if (!list) return;

  const users = await api("get_online_users");
  list.textContent = "";

  if (!users || users.length === 0) {
    const noOnline = document.createElement("div");
    noOnline.style.padding = "6px 12px";
    noOnline.style.fontSize = "0.8rem";
    noOnline.style.color = "var(--text-secondary)";
    noOnline.textContent = "オンラインユーザーなし";
    list.appendChild(noOnline);
    return;
  }

  const statusLabels = {
    online: "連絡可能",
    busy: "取り込み中",
    not_allowed: "応答不可",
    step_out: "一時退席中",
    going_away: "外出中",
    away: "退席中",
  };

  users.forEach((u) => {
    const item = document.createElement("div");
    item.style.cssText =
      "display:flex; align-items:center; gap:8px; padding:5px 10px; cursor:pointer; border-radius:4px; transition:background 0.15s;";
    item.onmouseenter = () =>
      (item.style.background = "var(--hover-bg, rgba(255,255,255,0.05))");
    item.onmouseleave = () => (item.style.background = "transparent");

    const avatarEl = getAvatarElement(
      u.username,
      u.status || "online",
      u.avatar_url,
    );
    avatarEl.style.transform = "scale(0.8)";
    avatarEl.style.transformOrigin = "left center";

    const info = document.createElement("div");
    info.style.cssText = "flex:1; min-width:0;";
    const nameDiv = document.createElement("div");
    nameDiv.style.cssText =
      "font-size:0.8rem; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;";
    nameDiv.textContent = u.username || "";

    const statusDiv = document.createElement("div");
    statusDiv.style.cssText = "font-size:0.68rem; color:var(--text-secondary);";
    statusDiv.textContent = statusLabels[u.status] || u.status || "";

    info.appendChild(nameDiv);
    info.appendChild(statusDiv);

    item.appendChild(avatarEl);
    item.appendChild(info);
    item.onclick = () => showUserProfile(u.id, u.username);
    list.appendChild(item);
  });
}

// ========== 新機能: DM未読バッジ ==========
async function updateUnreadDmBadge() {
  const res = await api("get_unread_dm_counts");
  if (!res || res.error) return;

  const badge = document.getElementById("dm-unread-badge");
  if (!badge) return;

  if (res.total > 0) {
    badge.style.display = "inline-block";
    badge.innerText = res.total > 99 ? "99+" : res.total;
  } else {
    badge.style.display = "none";
    badge.innerText = "";
  }

  // フレンドリストの各アイテムにもバッジを付与
  if (res.counts) {
    Object.entries(res.counts).forEach(([senderId, count]) => {
      const el = document.getElementById(`hub-friend-unread-${senderId}`);
      if (el) {
        el.style.display = count > 0 ? "inline-block" : "none";
        el.innerText = count > 9 ? "9+" : count;
      }
    });
  }
}

// フレンドリストにバッジを付与するためloadHubFriendsを拡張
const _origLoadHubFriends = loadHubFriends;
loadHubFriends = async function () {
  await _origLoadHubFriends();
  // バッジ要素を各フレンドアイテムに追加
  const friends = document.querySelectorAll("#hub-friend-list .thread-item");
  // Note: バッジは動的に未読カウント取得後に反映されるため再取得
  await updateUnreadDmBadge();
};

// ========== 新機能: キーボードショートカット ==========
document.addEventListener("keydown", (e) => {
  const focused = document.activeElement;
  const isInputFocused =
    focused &&
    (focused.tagName === "INPUT" ||
      focused.tagName === "TEXTAREA" ||
      focused.tagName === "SELECT");

  // Esc: リプライキャンセル / 検索結果を閉じる / モーダルを閉じる
  if (e.key === "Escape") {
    const overlay = document.getElementById("search-results-overlay");
    if (overlay && overlay.style.display !== "none") {
      toggleSearch(false);
      return;
    }
    if (replyToId) {
      cancelReply();
      return;
    }
    // 開いているモーダルを閉じる
    const openDialogs = document.querySelectorAll("dialog[open]");
    openDialogs.forEach((d) => d.close());
    return;
  }

  // Alt+? : キーボードショートカット一覧
  if (e.altKey && e.key === "?") {
    e.preventDefault();
    document.getElementById("keyboard-shortcuts-modal").showModal();
    return;
  }

  // Alt+P : ピン留め一覧
  if (e.altKey && (e.key === "p" || e.key === "P")) {
    e.preventDefault();
    showPinnedMessages();
    return;
  }

  // / : 入力フィールドにフォーカスがない場合、検索入力にフォーカス
  if (e.key === "/" && !isInputFocused) {
    e.preventDefault();
    const searchInput = document.getElementById("search-input");
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
    return;
  }
});

// --- Typing Indicator ---
let typingTimeout = null;
let isTypingSent = false;

function updateTypingStatus(isTyping) {
  if (isTyping === isTypingSent) return;
  isTypingSent = isTyping;

  const body = new FormData();
  // Use a specific negative ID or prefix for DMs to avoid collision?
  // Better: use currentThreadId which is 0 or null for DM, but we need to distinguish partners.
  // Let's use partner_id for DMs.
  const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
  body.append("thread_id", targetId);
  body.append("is_typing", isTyping ? "1" : "0");
  api("update_typing_status", "POST", body);
}

function handleTyping() {
  if (socket) {
    const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
    socket.emit("typing", {
      threadId: targetId,
      userId: currentUserId,
      username: currentUserName,
      isTyping: true,
    });
  }
  updateTypingStatus(true);
  if (typingTimeout) clearTimeout(typingTimeout);
  typingTimeout = setTimeout(() => {
    if (socket) {
      const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
      socket.emit("typing", {
        threadId: targetId,
        userId: currentUserId,
        username: currentUserName,
        isTyping: false,
      });
    }
    updateTypingStatus(false);
  }, 3000);
}

async function fetchTypingUsers() {
  const targetId = isDmMode ? `dm_${currentPartnerId}` : currentThreadId;
  if (!targetId) return;
  const res = await api(`get_typing_users&thread_id=${targetId}`);
  const indicator = document.getElementById(
    isDmMode ? "dm-typing-indicator" : "typing-indicator",
  );
  if (indicator) {
    if (res.length > 0) {
      const names = res.map((u) => u.username).join(", ");
      indicator.innerText = `${names} が入力中...`;
      indicator.style.visibility = "visible";
    } else {
      indicator.innerText = "";
      indicator.style.visibility = "hidden";
    }
  }
}

function startEditMessage(m, isDm) {
  const newContent = prompt("メッセージを編集:", m.content);
  if (newContent !== null && newContent !== m.content) {
    saveEditMessage(m.id, newContent, isDm);
  }
}

async function saveEditMessage(id, content, isDm) {
  const body = new FormData();
  if (isDm) body.append("dm_id", id);
  else body.append("message_id", id);
  body.append("content", content);
  const res = await api("edit_message", "POST", body);
  if (res.success) {
    if (isDm) loadDms();
    else if (isGroupMode) loadGroupMessages();
    else loadMessages();
  } else {
    alert("編集に失敗しました");
  }
}

let tacticalMap = null;
let mapMarkers = {};

function initTacticalMap() {
  if (tacticalMap) {
    tacticalMap.remove();
    tacticalMap = null;
  }

  // Default to Tokyo if no GPS
  const lat = locationManager.gpsData.lat || 35.6812;
  const lon = locationManager.gpsData.lon || 139.7671;

  tacticalMap = L.map("tac-map-container", {
    zoomControl: false,
    attributionControl: false,
  }).setView([lat, lon], 15);

  L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
    maxZoom: 20,
  }).addTo(tacticalMap);

  L.control
    .zoom({
      position: "bottomright",
    })
    .addTo(tacticalMap);

  updateMapMarkers();
  // 既にインターバルが設定されている場合は重複を避ける（本来は一箇所で管理すべきだが）
}

async function updateMapMarkers() {
  if (
    !tacticalMap ||
    !document.getElementById("tactical-map-pane").classList.contains("active")
  )
    return;

  const locations = await api("get_user_locations");

  const statusHeader = document.getElementById("gps-status-header");
  if (statusHeader && locationManager.gpsData.lat) {
    statusHeader.innerText = `自機位置: ${locationManager.gpsData.lat.toFixed(4)}, ${locationManager.gpsData.lon.toFixed(4)}`;
  }

  const currentIds = locations.map((l) => l.user_id.toString());
  Object.keys(mapMarkers).forEach((id) => {
    if (!currentIds.includes(id)) {
      tacticalMap.removeLayer(mapMarkers[id]);
      delete mapMarkers[id];
    }
  });

  locations.forEach((loc) => {
    const id = loc.user_id;
    const latlon = [loc.lat, loc.lon];
    const isMe = id == currentUserId;

    if (mapMarkers[id]) {
      mapMarkers[id].setLatLng(latlon);
    } else {
      const icon = L.divIcon({
        className: "custom-div-icon",
        html: `<div class="marker-pin ${isMe ? "me" : ""}" style="background-image: url('${loc.avatar_url || "assets/img/default-avatar.png"}')"></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
      });

      const marker = L.marker(latlon, {
        icon: icon,
      }).addTo(tacticalMap);
      marker.bindPopup(
        `<strong>${loc.username}</strong><br>精度: ${loc.accuracy}m<br>更新: ${loc.updated_at}`,
      );
      mapMarkers[id] = marker;
    }
  });
}

// 定期更新用のインターバルを設定（一度だけ）
setInterval(updateMapMarkers, 10000);

async function showAttachmentGallery() {
  const modal = document.getElementById("gallery-modal");
  const content = document.getElementById("gallery-content");
  content.textContent = "";
  const loading = document.createElement("div");
  loading.style.gridColumn = "1/-1";
  loading.style.textAlign = "center";
  loading.textContent = "読み込み中...";
  content.appendChild(loading);
  modal.showModal();

  const url = isDmMode
    ? `get_attachments&partner_id=${currentPartnerId}`
    : `get_attachments&thread_id=${currentThreadId}`;
  const files = await api(url);

  content.textContent = "";
  if (files.length === 0) {
    const noFiles = document.createElement("div");
    noFiles.style.gridColumn = "1/-1";
    noFiles.style.textAlign = "center";
    noFiles.style.color = "var(--text-secondary)";
    noFiles.textContent = "添付ファイルはありません";
    content.appendChild(noFiles);
    return;
  }

  files.forEach((f) => {
    const path = f.attachment_path;
    const ext = path.split(".").pop().toLowerCase();
    const isImage = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(ext);

    const item = document.createElement("div");
    item.style.background = "var(--card-bg)";
    item.style.borderRadius = "8px";
    item.style.overflow = "hidden";
    item.style.border = "1px solid var(--border-color)";
    item.style.cursor = "pointer";
    item.onclick = () => window.open(path, "_blank");

    if (isImage) {
      const img = document.createElement("img");
      img.src = path;
      img.style.width = "100%";
      img.style.height = "120px";
      img.style.objectFit = "cover";
      item.appendChild(img);
    } else {
      const placeholder = document.createElement("div");
      placeholder.style.height = "120px";
      placeholder.style.display = "flex";
      placeholder.style.flexDirection = "column";
      placeholder.style.justifyContent = "center";
      placeholder.style.alignItems = "center";
      placeholder.style.fontSize = "2rem";
      placeholder.textContent = "📄";
      const nameDiv = document.createElement("div");
      nameDiv.style.fontSize = "0.7rem";
      nameDiv.style.marginTop = "8px";
      nameDiv.style.padding = "0 4px";
      nameDiv.style.overflow = "hidden";
      nameDiv.style.textOverflow = "ellipsis";
      nameDiv.style.width = "100%";
      nameDiv.style.textAlign = "center";
      nameDiv.textContent = path.split("/").pop();
      placeholder.appendChild(nameDiv);
      item.appendChild(placeholder);
    }
    content.appendChild(item);
  });
}

// PWA Service Worker Registration
if ("serviceWorker" in navigator) {
  window.addEventListener("load", async () => {
    try {
      const registration = await navigator.serviceWorker.register("./sw.js", {
        scope: "./",
      });
      console.log("[PWA] Service Worker 登録成功:", registration.scope);

      // Check for updates periodically
      setInterval(
        () => {
          registration.update();
        },
        60 * 60 * 1000,
      ); // 1時間ごと
    } catch (error) {
      console.warn("[PWA] Service Worker 登録失敗:", error);
    }
  });
}

// PWA Install Prompt
let deferredPrompt = null;

function showPwaInstallBanners(show = true) {
  const bannerThreads = document.getElementById("pwa-install-banner-threads");
  const bannerDm = document.getElementById("pwa-install-banner-dm");
  const display = show ? "flex" : "none";
  if (bannerThreads) bannerThreads.style.display = display;
  if (bannerDm) bannerDm.style.display = display;
}

window.addEventListener("beforeinstallprompt", (e) => {
  e.preventDefault();
  deferredPrompt = e;
  setTimeout(() => showPwaInstallBanners(), 1000);
});

document.addEventListener("DOMContentLoaded", () => {
  if (!localStorage.getItem("pwa-install-dismissed")) {
    setTimeout(() => showPwaInstallBanners(), 3000);
  }
});

async function installPWA() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  deferredPrompt = null;
  showPwaInstallBanners(false);
}

function dismissInstallBanner() {
  const bannerThreads = document.getElementById("pwa-install-banner-threads");
  const bannerDm = document.getElementById("pwa-install-banner-dm");
  if (bannerThreads) bannerThreads.style.display = "none";
  if (bannerDm) bannerDm.style.display = "none";
  localStorage.setItem("pwa-install-dismissed", Date.now());
}

// Online/Offline detection
window.addEventListener("online", () => {
  const indicator = document.getElementById("offline-indicator");
  if (indicator) indicator.style.display = "none";
  console.log("[PWA] オンラインに復帰");
});

window.addEventListener("offline", () => {
  const indicator = document.getElementById("offline-indicator");
  if (indicator) indicator.style.display = "block";
  console.log("[PWA] オフラインになりました");
});

// Check initial state
if (!navigator.onLine) {
  const indicator = document.getElementById("offline-indicator");
  if (indicator) indicator.style.display = "block";
}

// App installed event
window.addEventListener("appinstalled", () => {
  console.log("[PWA] アプリがインストールされました");
  deferredPrompt = null;
  const bannerThreads = document.getElementById("pwa-install-banner-threads");
  const bannerDm = document.getElementById("pwa-install-banner-dm");
  if (bannerThreads) bannerThreads.style.display = "none";
  if (bannerDm) bannerDm.style.display = "none";
});

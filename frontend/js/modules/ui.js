/**
 * SYCS UI Module
 */

import { t, getAvatarElement } from './utils.js';
import { api, registerShowToast } from './api.js';

/**
 * トースト通知を表示する
 * @param {string} title タイトル
 * @param {string} message メッセージ内容
 * @param {string} type 'success', 'error', 'warning'
 * @param {number} duration 表示時間（ミリ秒）
 */
export function showToast(title, message, type = "success", duration = 5000) {
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
    info:
      '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
  };

  const iconContainer = document.createElement("div");
  iconContainer.className = "toast-icon";
  if (iconMap[type]) {
    const parser = new DOMParser();
    try {
      const svgDoc = parser.parseFromString(iconMap[type], "image/svg+xml");
      if (svgDoc.getElementsByTagName("parsererror").length > 0) {
        throw new Error("SVG parse error");
      }
      const svgElement = svgDoc.documentElement;
      iconContainer.appendChild(svgElement);
    } catch (parseError) {
      console.error("Icon parsing failed:", parseError);
    }
  }

  const toastContent = document.createElement("div");
  toastContent.className = "toast-content";

  const toastTitle = document.createElement("div");
  toastTitle.className = "toast-title";
  toastTitle.textContent = title;

  const toastMsg = document.createElement("div");
  toastMsg.className = "toast-message";
  toastMsg.textContent = message;

  toastContent.appendChild(toastTitle);
  toastContent.appendChild(toastMsg);

  const closeBtn = document.createElement("div");
  closeBtn.className = "toast-close";
  const closeSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  closeSvg.setAttribute("viewBox", "0 0 24 24");
  closeSvg.setAttribute("width", "16");
  closeSvg.setAttribute("height", "16");
  closeSvg.setAttribute("fill", "none");
  closeSvg.setAttribute("stroke", "currentColor");
  closeSvg.setAttribute("stroke-width", "2");
  closeSvg.setAttribute("stroke-linecap", "round");
  closeSvg.setAttribute("stroke-linejoin", "round");
  const line1 = document.createElementNS("http://www.w3.org/2000/svg", "line");
  line1.setAttribute("x1", "18");
  line1.setAttribute("y1", "6");
  line1.setAttribute("x2", "6");
  line1.setAttribute("y2", "18");
  const line2 = document.createElementNS("http://www.w3.org/2000/svg", "line");
  line2.setAttribute("x1", "6");
  line2.setAttribute("y1", "6");
  line2.setAttribute("x2", "18");
  line2.setAttribute("y2", "18");
  closeSvg.appendChild(line1);
  closeSvg.appendChild(line2);
  closeBtn.appendChild(closeSvg);

  toast.appendChild(iconContainer);
  toast.appendChild(toastContent);
  toast.appendChild(closeBtn);

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

  closeBtn.onclick = removeToast;

  if (duration > 0) {
    setTimeout(removeToast, duration);
  }
}

// Register toast in API module
registerShowToast(showToast);

/**
 * Update current user status
 * @param {string} status 
 */
export async function updateMyStatus(status) {
  const body = new FormData();
  body.append("status", status);
  const res = await api("update_status", "POST", body);
  if (res && res.success) {
    const indicator = document.getElementById("global-status-indicator");
    if (indicator) indicator.className = `status-indicator status-${status}`;

    const sidebarInput = document.getElementById("sidebar-status-input");
    const modalInput = document.getElementById("modal-status-input");
    if (sidebarInput) sidebarInput.value = status;
    if (modalInput) modalInput.value = status;
  }
}

/**
 * Toggle Sidebar visibility
 * @param {boolean} force 
 */
export function toggleSidebar(force) {
  const sidebar = document.getElementById("main-sidebar");
  const backdrop = document.querySelector(".sidebar-backdrop");
  if (!sidebar) return;
  if (typeof force === "boolean") {
    sidebar.classList.toggle("active", force);
    if (backdrop) backdrop.classList.toggle("active", force);
  } else {
    const isActive = sidebar.classList.toggle("active");
    if (backdrop) backdrop.classList.toggle("active", isActive);
  }
}

/**
 * Toggle Thread Browser visibility
 */
export function toggleThreadBrowser() {
  const browser = document.getElementById("thread-browser");
  if (browser) browser.classList.toggle("active");
}

/**
 * Toggle Advanced Search panel
 */
export function toggleAdvancedSearch() {
  const panel = document.getElementById("advanced-search-panel");
  if (panel) {
    const isVisible = panel.style.display === "flex";
    panel.style.display = isVisible ? "none" : "flex";
  }
}

/**
 * Toggle Search results overlay
 * @param {boolean} show 
 */
export function toggleSearch(show) {
  const overlay = document.getElementById("search-results-overlay");
  if (overlay) overlay.classList.toggle("active", show);
}

/**
 * Switch main navigation tabs
 * @param {string} tabName 
 */
export function switchTab(tabName) {
  // Update nav UI
  document.querySelectorAll(".nav-item").forEach(item => {
    item.classList.toggle("active", item.dataset.tab === tabName);
  });

  // Update content panes
  document.querySelectorAll(".content-pane").forEach(pane => {
    pane.classList.toggle("active", pane.id === `${tabName}-pane`);
  });

  if (tabName === 'dm' && typeof window.loadFriends === 'function') {
    window.loadFriends();
  }
}

/**
 * Switch sidebar widgets
 * @param {string} widgetId 
 */
export function switchWidget(widgetId) {
  // Update tabs
  document.querySelectorAll(".widget-tab").forEach(tab => {
    tab.classList.toggle("active", tab.dataset.widget === widgetId);
  });

  // Update panes
  document.querySelectorAll(".widget-pane").forEach(pane => {
    pane.classList.toggle("active", pane.id === `widget-${widgetId}`);
  });
}

/**
 * Show standard modal by ID
 * @param {string} id 
 */
export function showModal(id) {
  const modal = document.getElementById(id);
  if (modal && typeof modal.showModal === "function") {
    modal.showModal();
    if (id === "media-upload-modal") {
      setupDropzone();
    }
  }
}

function setupDropzone() {
  const dropzone = document.getElementById("media-upload-dropzone");
  if (!dropzone) return;
  if (dropzone.dataset.initialized) return;
  dropzone.dataset.initialized = "true";

  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.add('dragover');
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.remove('dragover');
    }, false);
  });

  dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleMediaUploadFiles(files);
  }, false);
}

/**
 * Close standard modal by ID
 * @param {string} id 
 */
export function closeModal(id) {
  if (id === "media-upload-modal") {
    closeMediaUploadModal();
    return;
  }
  const modal = document.getElementById(id);
  if (modal && typeof modal.close === "function") {
    modal.close();
  }
}

/**
 * Toggle Map visibility
 */
export function toggleMap() {
  const mapPane = document.getElementById("map-pane");
  if (mapPane) {
    const isActive = mapPane.classList.contains("active");
    switchTab(isActive ? "threads" : "map");
  }
}

/**
 * Toggle GPS tracking
 */
export function toggleGPS() {
  if (typeof window.locationManager !== "undefined") {
    if (window.locationManager.watchId) {
      window.locationManager.stop();
      showToast("GPS", t("gps_stopped", "GPS停止"), "warning");
    } else {
      window.locationManager.start();
      showToast("GPS", t("gps_started", "GPS開始"), "success");
    }
  }
}

/**
 * Show Attachment Gallery
 */
export async function showAttachmentGallery() {
  showModal("gallery-modal");
  // Logic to load gallery content via API
  const content = document.getElementById("gallery-content");
  if (content) {
    content.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
    const threadId = window.SYCS_CONFIG?.currentThreadId || 0;
    const res = await api(`get_attachments&thread_id=${threadId}`);
    content.innerHTML = "";
    if (res && res.length > 0) {
      res.forEach(item => {
        const path = item.attachment_path;
        if (!path) return;
        const div = document.createElement("div");
        div.className = "gallery-item";
        const ext = path.split('.').pop().toLowerCase();
        const isImage = ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
        if (isImage) {
          div.innerHTML = `<img src="${path}" alt="" loading="lazy" style="width:100%; height:150px; object-fit:cover; border-radius:8px;">` ;
        } else {
          div.innerHTML = `<div style="padding:20px; text-align:center; font-size:0.8rem; opacity:0.7;">${path.split('/').pop()}</div>`;
        }
        div.onclick = () => window.open(path, '_blank');
        content.appendChild(div);
      });
    } else {
      content.innerHTML = `<div class="empty-state">${t("no_attachments", "ファイルはありません")}</div>`;
    }
  }
}

/**
 * Show Pinned Messages
 */
export async function showPinnedMessages() {
  showModal("pinned-messages-modal");
  const list = document.getElementById("pinned-messages-list");
  if (list) {
    list.innerHTML = `<div class="loading">${t("loading", "読み込み中...")}</div>`;
    const threadId = window.SYCS_CONFIG?.currentThreadId || 0;
    const res = await api(`get_pinned_messages&thread_id=${threadId}`);
    list.innerHTML = "";
    if (res && res.length > 0) {
      res.forEach(msg => {
        const div = document.createElement("div");
        div.className = "pinned-item";
        div.style.cssText = "padding: 12px; border-bottom: 1px solid var(--border-color); cursor: pointer;";
        div.innerHTML = `
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <span style="font-weight:600; font-size:0.9rem;">${msg.username || 'Unknown'}</span>
            <span style="font-size:0.75rem; opacity:0.6;">${msg.created_at || ''}</span>
          </div>
          <div class="pinned-text" style="font-size:0.9rem; opacity:0.9;">${msg.content || ''}</div>
        `;
        div.onclick = () => {
          const target = document.getElementById("message-" + msg.id);
          if (target) {
            document.getElementById('pinned-messages-modal')?.close();
            target.scrollIntoView({ behavior: "smooth", block: "center" });
            target.style.backgroundColor = "rgba(99, 102, 241, 0.2)";
            setTimeout(() => (target.style.backgroundColor = ""), 2000);
          }
        };
        list.appendChild(div);
      });
    } else {
      list.innerHTML = `<div class="empty-state" style="padding:40px; text-align:center; color:var(--text-secondary);">${t("no_pinned", "ピン留めされたメッセージはありません")}</div>`;
    }
  }
}

/**
 * Change Application Language
 * @param {string} lang 
 */
export async function changeLang(lang) {
  const res = await fetch(`index.php?api=set_lang&lang=${lang}`);
  if (res.ok) {
    location.reload();
  }
}

/**
 * Toggle Online Users list in sidebar
 */
export function toggleOnlineUsers() {
  const list = document.getElementById("online-users-list");
  const icon = document.getElementById("online-users-toggle-icon");
  if (list) {
    const isHidden = list.style.display === "none";
    list.style.display = isHidden ? "block" : "none";
    if (icon) icon.innerText = isHidden ? "▾" : "▸";
  }
}

/**
 * アプリケーションのテーマを設定
 * @param {'dark' | 'light'} theme
 * @param {boolean} [showToastNotify=true] トースト通知を表示するかどうか
 */
export function setTheme(theme, showToastNotify = true) {
  const isLight = theme === "light";
  const isNight = theme === "night";
  // クラスの付け替え（他のクラスを破壊しない！）
  document.body.classList.toggle("light-theme", isLight);
  document.body.classList.toggle("dark-theme", !isLight);
  document.body.classList.toggle("night-theme", isNight);

  // 永続化
  localStorage.setItem("sycs_theme", theme);

  // 通知（showToastNotifyが真の時だけ実行）
  if (showToastNotify) {
    showToast(
      t("settings", "設定"), 
      t("theme_changed", "テーマを変更しました"), 
      "info"
    );
  }
}

/** 
 * PWAなどのインストール待機用（続きの処理用） 
 */
let deferredPrompt;
/**
 * Handle PWA install prompt
 */
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  // Show banners
  document.querySelectorAll("[id^='pwa-install-banner']").forEach(b => b.style.display = "flex");
});

/**
 * Install PWA
 */
export async function installPWA() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') {
    deferredPrompt = null;
    dismissInstallBanner();
  }
}

/**
 * Dismiss PWA Install Banner
 */
export function dismissInstallBanner() {
  document.querySelectorAll("[id^='pwa-install-banner']").forEach(b => b.style.display = "none");
}

/**
 * Start WebRTC Meeting
 */
export function startMeeting() {
  showToast(t("info", "情報"), t("meeting_start_clicked", "ミーティング機能は準備中です"), "info");
  // Logic for meetingManager would go here
}

/**
 * Handle Media Upload Files
 */
export function handleMediaUploadFiles(files) {
  if (!files || files.length === 0) return;
  const file = files[0];
  window.pendingUploadFile = file;

  const previewContainer = document.getElementById("media-upload-preview-container");
  if (!previewContainer) return;

  previewContainer.innerHTML = "";

  const isImage = file.type.startsWith("image/");
  if (isImage) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewContainer.innerHTML = `
        <div style="position:relative; width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center;">
          <img src="${e.target.result}" style="max-width:100%; max-height:200px; border-radius:8px; object-fit:contain;">
          <div style="margin-top:8px; font-size:0.85rem; color:var(--text-secondary); text-align:center; width:100%;">${file.name} (${formatBytes(file.size)})</div>
        </div>
      `;
    };
    reader.readAsDataURL(file);
  } else {
    previewContainer.innerHTML = `
      <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px; width:100%;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 10px;">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <p style="margin:0; font-weight:600; color:var(--text-primary); text-align:center; word-break:break-all;">${file.name}</p>
        <p style="margin:5px 0 0 0; font-size:0.8rem; color:var(--text-secondary);">${formatBytes(file.size)}</p>
      </div>
    `;
  }
}

function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

export function closeMediaUploadModal() {
  const modal = document.getElementById("media-upload-modal");
  if (modal && typeof modal.close === "function") {
    modal.close();
  }
  // リセット
  window.pendingUploadFile = null;
  const fileInput = document.getElementById("modal-file-input");
  if (fileInput) fileInput.value = "";
  const contentInput = document.getElementById("modal-content-input");
  if (contentInput) contentInput.value = "";
  
  const previewContainer = document.getElementById("media-upload-preview-container");
  if (previewContainer) {
    previewContainer.innerHTML = `
      <div class="upload-placeholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 15px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="17 8 12 3 7 8"></polyline>
          <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        <p style="margin:0; color:var(--text-secondary);">${t('click_or_drag_to_select', 'クリックまたはドラッグして選択')}</p>
      </div>
    `;
  }
}

export async function submitMediaUpload() {
  const contentInput = document.getElementById("modal-content-input");
  const content = contentInput ? contentInput.value.trim() : "";
  
  if (!window.pendingUploadFile) {
    showToast(t("error", "エラー"), t("select_file", "ファイルを選択してください"), "error");
    return;
  }

  const formData = new FormData();
  formData.append("content", content);
  formData.append("attachment", window.pendingUploadFile);
  formData.append("csrf_token", window.SYCS_CONFIG?.csrfToken || "");

  const isDmActive = document.getElementById("dm-pane") && document.getElementById("dm-pane").classList.contains("active");

  let res;
  if (isDmActive) {
    const rid = window.currentDmPartnerId;
    if (!rid) {
      showToast(t("error", "エラー"), t("select_dm_partner", "送信先の相手が選択されていません"), "error");
      return;
    }
    formData.append("receiver_id", rid);
    res = await api("send_direct_message", "POST", formData);
  } else {
    const threadId = window.SYCS_CONFIG.currentThreadId;
    const isGroup = window.SYCS_CONFIG.isGroupChat;
    if (isGroup) {
      formData.append("group_thread_id", threadId);
    } else {
      formData.append("thread_id", threadId);
    }
    res = await api("send_message", "POST", formData);
  }

  if (res && res.success) {
    showToast(t("success", "成功"), t("sent_successfully", "送信が完了しました"), "success");
    closeMediaUploadModal();

    // UIを更新
    if (isDmActive) {
      if (typeof window.switchToDm === "function") {
        window.switchToDm(window.currentDmPartnerId, document.getElementById("current-header-title").innerText);
      }
    } else {
      const container = document.getElementById("message-container");
      const currentUserName = window.SYCS_CONFIG.currentUserName;
      const currentUserId = window.SYCS_CONFIG.currentUserId;
      if (window.SYCS_CONFIG.isGroupChat) {
        import('./chat.js').then(m => m.loadGroupMessages(window.SYCS_CONFIG.currentThreadId, container, {currentUserName, currentUserId}, {}));
      } else {
        import('./chat.js').then(m => m.loadMessages(window.SYCS_CONFIG.currentThreadId, container, {currentUserName, currentUserId}, {}));
      }
    }
  } else {
    showToast(t("error", "エラー"), (res && res.error) ? res.error : t("failed_to_send", "送信に失敗しました"), "error");
  }
}

/**
 * Cancel DM Upload
 */
export function cancelDmUpload() {
  const preview = document.getElementById("dm-upload-preview");
  if (preview) preview.style.display = "none";
}

/**
 * Toggle Notification Mute
 */
export function toggleMute() {
  const isMuted = localStorage.getItem("sycs_muted") === "true";
  const newMuted = !isMuted;
  localStorage.setItem("sycs_muted", newMuted);
  
  const muteBtn = document.getElementById("mute-btn");
  if (muteBtn) {
    muteBtn.classList.toggle("muted", newMuted);
    muteBtn.innerHTML = newMuted ? 
      `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"></path><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>` :
      `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>`;
  }
  showToast(t("settings", "設定"), newMuted ? t("muted", "通知をミュートしました") : t("unmuted", "通知をオンにしました"), "info");
}

// Register toast function to API module
registerShowToast(showToast);

/**
 * Load and render online users in sidebar
 */
export async function loadOnlineUsers() {
  const list = document.getElementById("online-users-list");
  if (!list) return;

  const users = await api("get_online_users");
  list.innerHTML = "";

  if (Array.isArray(users) && users.length > 0) {
    users.forEach((u) => {
      const item = document.createElement("div");
      item.className = "thread-item online-user-item";
      item.style.cssText = "display: flex; align-items: center; gap: 10px; padding: 8px 16px; cursor: pointer; transition: background 0.2s;";
      item.dataset.id = u.id;

      // Avatar with status dot
      const avatarEl = getAvatarElement(u.username, u.status || 'online', u.avatar_url);
      avatarEl.style.cssText = "position: relative; flex-shrink: 0; width: 28px; height: 28px;";

      const innerAvatar = avatarEl.querySelector(".avatar");
      if (innerAvatar) {
        innerAvatar.style.width = "28px";
        innerAvatar.style.height = "28px";
        innerAvatar.style.borderRadius = "50%";
        innerAvatar.style.fontSize = "0.75rem";
      }

      const innerIndicator = avatarEl.querySelector(".status-indicator");
      if (innerIndicator) {
        innerIndicator.style.width = "10px";
        innerIndicator.style.height = "10px";
        innerIndicator.style.border = "1.5px solid var(--sidebar-bg, #1a1a2e)";
        innerIndicator.style.bottom = "-1px";
        innerIndicator.style.right = "-1px";
      }

      const nameSpan = document.createElement("span");
      nameSpan.textContent = u.username;
      nameSpan.style.cssText = "color: var(--text-primary); font-size: 0.9rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;";

      item.appendChild(avatarEl);
      item.appendChild(nameSpan);

      item.onclick = () => {
        if (typeof window.showUserProfile === "function") {
          window.showUserProfile(u.id);
        }
      };

      list.appendChild(item);
    });
  } else {
    const empty = document.createElement("div");
    empty.style.cssText = "padding: 12px 16px; font-size: 0.8rem; color: var(--text-muted); text-align: center;";
    empty.textContent = t("no_online_users", "オンラインユーザーはいません");
    list.appendChild(empty);
  }
}

/**
 * Show detailed user profile modal with options to send friend request or DM
 * @param {number} userId 
 */
export async function showUserProfile(userId) {
  const modal = document.getElementById("user-profile-modal");
  if (!modal) return;

  const usernameEl = document.getElementById("user-profile-username");
  const bioEl = document.getElementById("user-profile-bio");
  const bannerEl = document.getElementById("user-profile-banner");
  const avatarContainer = document.getElementById("user-profile-avatar-container");
  const statusIndicator = document.getElementById("user-profile-status-indicator");
  const snsEl = document.getElementById("user-profile-sns");
  const dmBtn = document.getElementById("user-profile-dm-btn");
  const friendBtn = document.getElementById("user-profile-friend-btn");

  if (usernameEl) usernameEl.textContent = t("loading", "読み込み中...");
  if (bioEl) bioEl.textContent = "";
  if (snsEl) snsEl.innerHTML = "";

  showModal("user-profile-modal");

  const res = await api(`get_user_profile&user_id=${userId}`);
  if (res && !res.error) {
    if (usernameEl) usernameEl.textContent = res.username || "";
    if (bioEl) bioEl.textContent = res.bio || "";

    // Banner
    if (bannerEl) {
      if (res.banner_url) {
        bannerEl.style.background = `url('${res.banner_url}') center/cover`;
      } else {
        bannerEl.style.background = res.banner_color || '#6366f1';
      }
    }

    // Avatar
    if (avatarContainer) {
      avatarContainer.innerHTML = "";
      const avatarEl = getAvatarElement(res.username, "none", res.avatar_url);
      const innerAvatar = avatarEl.querySelector(".avatar");
      if (innerAvatar) {
        innerAvatar.style.width = "90px";
        innerAvatar.style.height = "90px";
        innerAvatar.style.fontSize = "2rem";
        innerAvatar.style.borderRadius = "50%";
      }
      avatarContainer.appendChild(avatarEl);
    }

    // Status Indicator
    if (statusIndicator) {
      statusIndicator.className = `discord-status-indicator status-${res.status || 'offline'}`;
    }

    // Custom Status
    const customStatusEl = document.getElementById("user-profile-custom-status");
    if (customStatusEl) {
      customStatusEl.textContent = res.custom_status || "";
    }

    // Social links
    if (snsEl && res.social_links) {
      let social = res.social_links;
      if (typeof social === 'string') {
        try { social = JSON.parse(social); } catch(e) { social = {}; }
      }
      snsEl.innerHTML = "";
      if (social && social.twitter) {
        snsEl.innerHTML += `<a href="https://twitter.com/${encodeURIComponent(social.twitter)}" target="_blank" class="sns-link" style="color:var(--accent-color);">Twitter: ${social.twitter}</a>`;
      }
      if (social && social.github) {
        snsEl.innerHTML += `<a href="https://github.com/${encodeURIComponent(social.github)}" target="_blank" class="sns-link" style="color:var(--accent-color); margin-left:10px;">GitHub: ${social.github}</a>`;
      }
    }

    // DM Button click event
    if (dmBtn) {
      dmBtn.onclick = () => {
        modal.close();
        if (typeof window.switchToDm === "function") {
          window.switchToDm(res.id, res.username);
        }
      };
    }

    // Friend request button click event
    if (friendBtn) {
      friendBtn.onclick = async () => {
        const payload = new FormData();
        payload.append("target_id", res.id);
        const requestRes = await api("request_friend", "POST", payload);
        if (requestRes && requestRes.success) {
          showToast(t("success", "成功"), t("friend_request_sent", "フレンドリクエストを送信しました"), "success");
        } else {
          showToast(t("error", "エラー"), t("friend_request_failed", "リクエスト送信に失敗しました"), "error");
        }
      };
    }
  } else {
    if (usernameEl) usernameEl.textContent = t("error", "エラー");
    if (bioEl) bioEl.textContent = "プロフィールの取得に失敗しました。";
  }
}

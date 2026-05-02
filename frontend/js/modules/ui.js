/**
 * SYCS UI Module
 */

import { t } from './utils.js';
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
  if (!sidebar) return;
  if (typeof force === "boolean") {
    sidebar.classList.toggle("active", force);
  } else {
    sidebar.classList.toggle("active");
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
  }
}

/**
 * Close standard modal by ID
 * @param {string} id 
 */
export function closeModal(id) {
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
    const res = await api("get_attachments");
    content.innerHTML = "";
    if (res && res.length > 0) {
      res.forEach(item => {
        const div = document.createElement("div");
        div.className = "gallery-item";
        div.innerHTML = `<img src="${item.path}" alt="" loading="lazy">`;
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
    const res = await api("get_pinned_messages");
    list.innerHTML = "";
    if (res && res.length > 0) {
      res.forEach(msg => {
        const div = document.createElement("div");
        div.className = "pinned-item";
        div.innerHTML = `<div class="pinned-text">${msg.content}</div>`;
        list.appendChild(div);
      });
    } else {
      list.innerHTML = `<div class="empty-state">${t("no_pinned", "ピン留めされたメッセージはありません")}</div>`;
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

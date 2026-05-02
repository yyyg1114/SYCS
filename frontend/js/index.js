/**
 * SYCS Main Entry Point (Modularized)
 */

import { t, formatMessage, applyHighlighting, getAvatarElement, getSkeletonLoader } from './modules/utils.js';
import { api } from './modules/api.js';
import { showToast, updateMyStatus } from './modules/ui.js';
import { renderMessageNode } from './modules/message.js';
import { loadThreads, loadGroupThreads, loadMessages, loadGroupMessages } from './modules/chat.js';
import { initSocket, socket } from './modules/socket.js';

// --- Global State ---
let currentThreadId = window.SYCS_CONFIG.currentThreadId;
let currentThreadCreatorId = window.SYCS_CONFIG.currentThreadCreatorId;
const currentUserId = window.SYCS_CONFIG.currentUserId;
const currentUserName = window.SYCS_CONFIG.currentUserName;
const currentUserTheme = window.SYCS_CONFIG.currentUserTheme;

// Export globals for HTML onclick handlers
window.switchThread = switchThread;
window.sendMessage = sendMessage;
window.cancelReply = cancelReply;
window.toggleSidebar = (f) => import('./modules/ui.js').then(m => m.toggleSidebar(f));
window.toggleThreadBrowser = () => import('./modules/ui.js').then(m => m.toggleThreadBrowser());
window.toggleAdvancedSearch = () => import('./modules/ui.js').then(m => m.toggleAdvancedSearch());
window.toggleSearch = (s) => import('./modules/ui.js').then(m => m.toggleSearch(s));
window.updateMyStatus = (s) => import('./modules/ui.js').then(m => m.updateMyStatus(s));
window.searchMessages = () => import('./modules/chat.js').then(m => m.searchMessages());
window.toggleFavorite = () => import('./modules/chat.js').then(m => m.toggleFavorite(currentThreadId));
window.showProfileModal = () => import('./modules/ui.js').then(m => m.showModal("profile-modal"));
window.showAttachmentGallery = () => import('./modules/ui.js').then(m => m.showAttachmentGallery());
window.showPinnedMessages = () => import('./modules/ui.js').then(m => m.showPinnedMessages());
window.toggleGPS = () => import('./modules/ui.js').then(m => m.toggleGPS());
window.toggleMap = () => import('./modules/ui.js').then(m => m.toggleMap());
window.changeLang = (l) => import('./modules/ui.js').then(m => m.changeLang(l));
window.closeModal = (id) => import('./modules/ui.js').then(m => m.closeModal(id));
window.editCurrentThread = () => import('./modules/chat.js').then(m => m.editCurrentThread());
window.deleteCurrentThread = () => import('./modules/chat.js').then(m => m.deleteCurrentThread());
window.openMediaUploadModal = () => import('./modules/ui.js').then(m => m.showModal("media-upload-modal"));
window.closeMediaUploadModal = () => import('./modules/ui.js').then(m => m.closeModal("media-upload-modal"));
window.previewAvatar = (i) => import('./modules/profile.js').then(m => m.previewAvatar(i));
window.removeAvatarPreview = () => import('./modules/profile.js').then(m => m.removeAvatarPreview());
window.updatePreviewBanner = (c) => import('./modules/profile.js').then(m => m.updatePreviewBanner(c));
window.previewBannerImage = (i) => import('./modules/profile.js').then(m => m.previewBannerImage(i));
window.removeBannerPreview = () => import('./modules/profile.js').then(m => m.removeBannerPreview());
window.updatePreviewLayout = (l) => import('./modules/profile.js').then(m => m.updatePreviewLayout(l));
window.updateAccentColor = (c) => import('./modules/profile.js').then(m => m.updateAccentColor(c));
window.updatePreviewBio = (b) => import('./modules/profile.js').then(m => m.updatePreviewBio(b));
window.updatePreviewStatus = (s) => import('./modules/profile.js').then(m => m.updatePreviewStatus(s));
window.saveProfile = () => import('./modules/profile.js').then(m => m.saveProfile());

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initApp();
    setupEventListeners();
});

async function initApp() {
    loadThreads(th => switchThread(th.id, th.name, th.creator_id));
    
    initSocket(currentUserId, {
        onNewMessage: (data) => {
            if (data.threadId == currentThreadId) {
                loadMessages(currentThreadId, document.getElementById("message-container"), {currentUserName, currentUserId}, getMessageCallbacks());
            }
        }
    });
}

function setupEventListeners() {
    // Nav Tabs
    document.querySelectorAll(".nav-item").forEach(item => {
        item.addEventListener("click", () => {
            const tab = item.dataset.tab;
            if (tab) {
                import('./modules/ui.js').then(m => m.switchTab(tab));
            }
        });
    });

    // Widget Tabs
    document.querySelectorAll(".widget-tab").forEach(tab => {
        tab.addEventListener("click", () => {
            const widget = tab.dataset.widget;
            if (widget) {
                import('./modules/ui.js').then(m => m.switchWidget(widget));
            }
        });
    });
}

function getMessageCallbacks() {
    return {
        onReply: (id, name, text) => {
            document.getElementById("reply-bar").classList.add("active");
            document.getElementById("reply-target-name").innerText = name;
            document.getElementById("reply-preview-text").innerText = text;
            window.replyToId = id;
        }
    };
}

async function switchThread(id, name, creatorId) {
    currentThreadId = id;
    currentThreadCreatorId = creatorId || 0;
    document.getElementById("current-thread-name").innerText = name;
    await loadMessages(id, document.getElementById("message-container"), {currentUserName, currentUserId}, getMessageCallbacks());
    
    // Update Sidebar focus if needed
    document.querySelectorAll(".thread-item").forEach(item => {
        item.classList.toggle("active", item.textContent.includes(name));
    });
}

function cancelReply() {
    document.getElementById("reply-bar").classList.remove("active");
    window.replyToId = null;
}

async function sendMessage() {
    const input = document.getElementById("msg-input");
    const content = input.value.trim();
    if (!content) return;

    const res = await api("send_message", "POST", {
        thread_id: currentThreadId,
        content: content,
        reply_to_id: window.replyToId
    });

    if (res.success) {
        input.value = "";
        cancelReply();
        loadMessages(currentThreadId, document.getElementById("message-container"), {currentUserName, currentUserId}, getMessageCallbacks());
    }
}

// ... Rest of the migrated functions from index.js
// Note: I will append more functions here or move them to modules.

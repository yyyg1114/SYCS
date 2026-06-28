/**
 * SYCS Main Entry Point (Modularized)
 */

import { t, formatMessage, applyHighlighting, getAvatarElement, getSkeletonLoader } from './modules/utils.js';
import { api } from './modules/api.js';
import { showToast, updateMyStatus, loadOnlineUsers } from './modules/ui.js';
import { renderMessageNode } from './modules/message.js';
import { loadThreads, loadGroupThreads, loadMessages, loadGroupMessages, updateFavoriteStatus } from './modules/chat.js';
import { initSocket, socket } from './modules/socket.js';
import { initNotifications, showBrowserNotification, requestNotificationPermission, updateTabBadge, resetTabBadge, trackUnread, clearUnread } from './modules/notifications.js';

// --- Emoji Picker state ---
let emojiPickerTarget = null;

// --- Global State ---
let currentThreadId = parseInt(window.SYCS_CONFIG.currentThreadId) || 1;
let currentThreadCreatorId = window.SYCS_CONFIG.currentThreadCreatorId;
let isGroupChat = window.SYCS_CONFIG.isGroupChat || false;
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
window.showProfileModal = () => import('./modules/ui.js').then(m => {
    m.showModal("profile-modal");
    window.loadPersistedProfileInputs();
});
window.showAttachmentGallery = () => import('./modules/ui.js').then(m => m.showAttachmentGallery());
window.showPinnedMessages = () => import('./modules/ui.js').then(m => m.showPinnedMessages());
window.toggleGPS = () => import('./modules/ui.js').then(m => m.toggleGPS());
window.toggleMap = () => import('./modules/ui.js').then(m => m.toggleMap());
window.changeLang = (l) => import('./modules/ui.js').then(m => m.changeLang(l));
window.closeModal = (id) => import('./modules/ui.js').then(m => m.closeModal(id));
window.editCurrentThread = () => import('./modules/chat.js').then(m => m.editCurrentThread());
window.deleteCurrentThread = () => import('./modules/chat.js').then(m => m.deleteCurrentThread());
window.openMediaUploadModal = () => import('./modules/ui.js').then(m => m.showModal("media-upload-modal"));
window.closeMediaUploadModal = () => import('./modules/ui.js').then(m => m.closeMediaUploadModal());
window.submitMediaUpload = () => import('./modules/ui.js').then(m => m.submitMediaUpload());
window.switchSidebarTab = (t) => import('./modules/chat.js').then(m => m.switchSidebarTab(t));
window.switchTab = (t) => import('./modules/ui.js').then(m => m.switchTab(t));
window.createThread = () => import('./modules/chat.js').then(m => m.createThread());
window.toggleOnlineUsers = () => import('./modules/ui.js').then(m => m.toggleOnlineUsers());
window.setTheme = (t) => import('./modules/ui.js').then(m => m.setTheme(t));
window.toggleMute = () => import('./modules/ui.js').then(m => m.toggleMute());
window.showAddFriendModal = () => import('./modules/friends.js').then(m => m.showAddFriendModal());
window.loadFriends = () => import('./modules/friends.js').then(m => m.loadFriends());
window.searchUsers = () => import('./modules/friends.js').then(m => m.searchUsers());
window.sendFriendRequest = (id) => import('./modules/friends.js').then(m => m.sendFriendRequest(id));
window.showPendingRequestsModal = () => import('./modules/friends.js').then(m => m.showPendingRequestsModal());
window.handleFriendRequest = (id, a) => import('./modules/friends.js').then(m => m.handleFriendRequest(id, a));
window.showBlockedModal = () => import('./modules/friends.js').then(m => m.showBlockedModal());
window.unblockUser = (id) => import('./modules/friends.js').then(m => m.unblockUser(id));
window.switchToDm = (id, n) => import('./modules/dm.js').then(m => m.switchToDm(id, n));
window.backToHub = () => import('./modules/dm.js').then(m => m.backToHub());
window.sendDm = () => import('./modules/dm.js').then(m => m.sendDm());
window.blockCurrentPartner = () => import('./modules/dm.js').then(m => m.blockCurrentPartner());
window.handleDmInputKey = (e) => import('./modules/dm.js').then(m => m.handleDmInputKey(e));
window.handleTyping = () => import('./modules/dm.js').then(m => m.handleTyping());
window.previewAvatar = (i) => import('./modules/profile.js').then(m => m.previewAvatar(i));
window.removeAvatarPreview = () => import('./modules/profile.js').then(m => m.removeAvatarPreview());
window.updatePreviewBanner = (c) => import('./modules/profile.js').then(m => m.updatePreviewBanner(c));
window.previewBannerImage = (i) => import('./modules/profile.js').then(m => m.previewBannerImage(i));
window.removeBannerPreview = () => import('./modules/profile.js').then(m => m.removeBannerPreview());
window.updatePreviewLayout = (l) => import('./modules/profile.js').then(m => m.updatePreviewLayout(l));
window.updateAccentColor = (c) => import('./modules/profile.js').then(m => m.updateAccentColor(c));
window.updatePreviewBio = (b) => import('./modules/profile.js').then(m => m.updatePreviewBio(b));
window.updatePreviewStatus = (s) => import('./modules/profile.js').then(m => m.updatePreviewStatus(s));
window.loadPersistedProfileInputs = () => import('./modules/profile.js').then(m => m.loadPersistedProfileInputs());
window.persistProfileInput = (id, v) => import('./modules/profile.js').then(m => m.persistProfileInput(id, v));
window.saveProfile = () => import('./modules/profile.js').then(m => m.saveProfile());
window.saveThreadSettings = () => import('./modules/chat.js').then(m => m.saveThreadSettings());
window.showGroupCreationDialog = () => import('./modules/chat.js').then(m => m.showGroupCreationDialog());
window.submitGroupCreation = () => import('./modules/chat.js').then(m => m.submitGroupCreation());
window.installPWA = () => import('./modules/ui.js').then(m => m.installPWA());
window.dismissInstallBanner = () => import('./modules/ui.js').then(m => m.dismissInstallBanner());
window.startMeeting = () => import('./modules/ui.js').then(m => m.startMeeting());
window.showUserProfile = (id) => import('./modules/ui.js').then(m => m.showUserProfile(id));
window.handleMediaUploadFiles = (f) => import('./modules/ui.js').then(m => m.handleMediaUploadFiles(f));
window.cancelDmUpload = () => import('./modules/ui.js').then(m => m.cancelDmUpload());
window.handleInputKey = handleInputKey;
window.cancelUpload = cancelUpload;
window.closeEmojiPicker = closeEmojiPicker;
window.toggleReactionPicker = toggleReactionPicker;
window.requestNotificationPermission = requestNotificationPermission;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initApp();
    setupEventListeners();
});

async function initApp() {
    // Apply Theme
    let themeToApply = localStorage.getItem('sycs_theme');
    if (!themeToApply && currentUserTheme && typeof currentUserTheme === 'object' && currentUserTheme.theme) {
        themeToApply = currentUserTheme.theme;
    }
    if (themeToApply) {
        import('./modules/ui.js').then(m => m.setTheme(themeToApply, false));
    }

    const threadList = document.getElementById("thread-list");
    if (threadList) {
        loadThreads(th => switchThread(th.id, th.name, th.creator_id));
        if (window.SYCS_CONFIG.isGroupChat) {
            import('./modules/chat.js').then(m => m.switchSidebarTab('groups'));
        }
        const initialName = document.getElementById("current-thread-name")?.innerText.replace(/^[#👥]\s*/, "") || "general";
        switchThread(currentThreadId, initialName, currentThreadCreatorId, isGroupChat);
    }
    
    initSocket(currentUserId, {
        onConnectError: (error) => {
            console.warn('Realtime server is unreachable. Some features may be limited.');
            // Only show toast once to avoid spamming
            if (!window._socketErrorShown) {
                showToast('Connection Error', 'Realtime server is offline. Notifications and live updates are disabled.', 'error');
                window._socketErrorShown = true;
            }
        },
        onConnect: () => {
            console.log('Connected to realtime server');
            window._socketErrorShown = false;
            resetTabBadge();
        },
        onNewMessage: (data) => {
            const container = document.getElementById("message-container");
            if (container && data.threadId == currentThreadId) {
                loadMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
            }
            // Show notification if it's not from current user
            if (data.userId != currentUserId) {
                showBrowserNotification(`New message in ${data.threadName || 'Thread'}`, {
                    body: `${data.username}: ${data.content}`,
                    tag: `thread-${data.threadId}`
                });
                
                // Track unread thread
                trackUnread(data.threadId);
                
                // Also update tab badge if window is not focused
                if (document.visibilityState !== 'visible') {
                    updateTabBadge();
                }
            }
        },
        onNewDm: (data) => {
            const isDmVisible = document.getElementById("dm-pane").classList.contains("active");
            if (isDmVisible) {
                // If DM pane is active, we might want to refresh or rely on modules/dm.js
                // modules/dm.js likely handles its own socket listeners or we can add it here
            }
            showBrowserNotification(`New DM from ${data.username}`, {
                body: data.content,
                tag: `dm-${data.userId}`
            });
        }
    });

    // Initialize Notifications & SW
    initNotifications();

    // Load online users
    loadOnlineUsers();
    setInterval(loadOnlineUsers, 30000);

    // Reset badge when window gets focus
    window.addEventListener('focus', () => {
        resetTabBadge();
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

    // Fallback for browsers without closedby support (e.g. Safari)
    if (!('closedBy' in HTMLDialogElement.prototype)) {
        document.addEventListener('click', (event) => {
            const dialog = event.target.closest('dialog[closedby="any"]');
            if (!dialog) return;

            // When clicking the backdrop, the event target is the dialog element itself.
            if (event.target !== dialog) return;

            const rect = dialog.getBoundingClientRect();
            const isDialogContent = (
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width
            );

            if (isDialogContent) return;

            // Clicked outside dialog content (backdrop), close the dialog
            if (typeof dialog.close === "function") {
                dialog.close();
            }
        });
    }
}

function getMessageCallbacks() {
    return {
        onReply: (id, name, text) => {
            document.getElementById("reply-bar").classList.add("active");
            document.getElementById("reply-target-name").innerText = name;
            document.getElementById("reply-preview-text").innerText = text;
            window.replyToId = id;
            document.getElementById("msg-input")?.focus();
        },

        onPin: async (messageId) => {
            const res = await api("toggle_pin", "POST", { message_id: messageId });
            if (res && res.success) {
                loadMessages(
                    currentThreadId,
                    document.getElementById("message-container"),
                    { currentUserName, currentUserId },
                    getMessageCallbacks()
                );
                showToast(t("pin", "ピン留め"), t("pin_toggled", "ピン状態を変更しました"), "success");
            }
        },

        onReact: (event, messageId) => {
            toggleReactionPicker(event, messageId);
        },

        onEdit: async (message, isDm) => {
            const wrapper = document.getElementById("message-" + message.id);
            if (!wrapper) return;
            const contentDiv = wrapper.querySelector(".message-content");
            if (!contentDiv) return;

            const original = message.content || "";
            const textarea = document.createElement("textarea");
            textarea.className = "chat-input edit-inline-input";
            textarea.value = original;
            textarea.rows = 2;
            textarea.style.width = "100%";
            textarea.style.marginTop = "4px";

            const btnRow = document.createElement("div");
            btnRow.style.display = "flex";
            btnRow.style.gap = "6px";
            btnRow.style.marginTop = "4px";

            const saveBtn = document.createElement("button");
            saveBtn.className = "btn-primary";
            saveBtn.style.fontSize = "0.75rem";
            saveBtn.style.padding = "4px 10px";
            saveBtn.textContent = t("save", "保存");
            saveBtn.onclick = async () => {
                const newContent = textarea.value.trim();
                if (!newContent) return;
                const payload = isDm
                    ? { dm_id: message.id, content: newContent }
                    : { message_id: message.id, content: newContent };
                const res = await api("edit_message", "POST", payload);
                if (res && res.success) {
                    loadMessages(
                        currentThreadId,
                        document.getElementById("message-container"),
                        { currentUserName, currentUserId },
                        getMessageCallbacks()
                    );
                }
            };

            const cancelBtn = document.createElement("button");
            cancelBtn.className = "btn-secondary";
            cancelBtn.style.fontSize = "0.75rem";
            cancelBtn.style.padding = "4px 10px";
            cancelBtn.textContent = t("cancel", "キャンセル");
            cancelBtn.onclick = () => {
                loadMessages(
                    currentThreadId,
                    document.getElementById("message-container"),
                    { currentUserName, currentUserId },
                    getMessageCallbacks()
                );
            };

            btnRow.appendChild(saveBtn);
            btnRow.appendChild(cancelBtn);
            contentDiv.replaceChildren(textarea, btnRow);
            textarea.focus();
        },

        onDelete: async (messageId) => {
            if (!confirm(t("delete_confirm", "このメッセージを削除してもよろしいですか？"))) return;
            const res = await api("delete_message", "POST", { message_id: messageId });
            if (res && res.success) {
                loadMessages(
                    currentThreadId,
                    document.getElementById("message-container"),
                    { currentUserName, currentUserId },
                    getMessageCallbacks()
                );
            }
        },

        onShowProfile: async (userId, username) => {
            import('./modules/ui.js').then(m => m.showModal("profile-modal"));
            const res = await api(`get_user_profile&user_id=${userId}`);
            if (res && !res.error) {
                const modal = document.getElementById("profile-modal");
                if (!modal) return;
                const nameEl = modal.querySelector("#profile-display-name, .profile-username, [data-field='username']");
                if (nameEl) nameEl.textContent = res.username || username;
                const bioEl = modal.querySelector("#profile-bio-display, .profile-bio, [data-field='bio']");
                if (bioEl) bioEl.textContent = res.bio || "";
            }
        },

        onToggleReaction: async (messageId, emoji) => {
            const res = await api("toggle_reaction", "POST", { message_id: messageId, emoji });
            if (res && res.success) {
                const container = document.getElementById("message-container");
                if (isGroupChat) {
                    loadGroupMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
                } else {
                    loadMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
                }
            }
        }
    };
}

async function switchThread(id, name, creatorId, isGroup = false) {
    id = parseInt(id);
    currentThreadId = id;
    currentThreadCreatorId = creatorId || 0;
    isGroupChat = isGroup;

    // ui モジュールや他モジュールがアクセスできるよう SYCS_CONFIG も更新
    window.SYCS_CONFIG.currentThreadId = id;
    window.SYCS_CONFIG.currentThreadCreatorId = creatorId || 0;
    window.SYCS_CONFIG.isGroupChat = isGroup;

    document.getElementById("current-thread-name").innerText = (isGroup ? "👥 " : "# ") + name;
    
    const container = document.getElementById("message-container");
    if (isGroup) {
        await loadGroupMessages(id, container, {currentUserName, currentUserId}, getMessageCallbacks());
    } else {
        await loadMessages(id, container, {currentUserName, currentUserId}, getMessageCallbacks());
    }
    
    await updateFavoriteStatus(id);
    
    // Clear unread state
    clearUnread(id);
    
    // Update Sidebar focus
    document.querySelectorAll(".thread-item").forEach(item => {
        const isThisItem = item.dataset.id == id && 
                         ((isGroup && item.textContent.includes("👥")) || (!isGroup && item.textContent.includes("#")));
        item.classList.toggle("active", isThisItem);
    });
}

function cancelReply() {
    document.getElementById("reply-bar").classList.remove("active");
    window.replyToId = null;
}

function cancelUpload() {
    const preview = document.getElementById("upload-preview");
    if (preview) preview.style.display = "none";
    window.pendingFile = null;
}

function handleInputKey(event) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

/**
 * リアクションピッカーを表示/非表示する
 */
function toggleReactionPicker(event, messageId) {
    event.stopPropagation();
    closeEmojiPicker();

    const emojis = ["👍", "❤️", "😂", "😮", "😢", "🎉", "🔥", "👀", "✅", "🙏"];

    const picker = document.createElement("div");
    picker.id = "emoji-picker-popup";
    picker.style.cssText = `
        position: fixed;
        background: var(--bg-secondary, #1e1e2e);
        border: 1px solid var(--border-color, #3a3a5c);
        border-radius: 12px;
        padding: 8px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        max-width: 200px;
        z-index: 9999;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    `;

    const rect = event.target.getBoundingClientRect();
    picker.style.left = Math.min(rect.left, window.innerWidth - 220) + "px";
    picker.style.top = (rect.top - 60) + "px";

    emojis.forEach(emoji => {
        const btn = document.createElement("button");
        btn.textContent = emoji;
        btn.style.cssText = "background:none; border:none; font-size:1.3rem; cursor:pointer; padding:4px; border-radius:6px; transition: transform 0.1s;";
        btn.onmouseenter = () => btn.style.transform = "scale(1.3)";
        btn.onmouseleave = () => btn.style.transform = "scale(1)";
        btn.onclick = async (e) => {
            e.stopPropagation();
            closeEmojiPicker();
            const res = await api("toggle_reaction", "POST", { message_id: messageId, emoji });
            if (res && res.success) {
                const container = document.getElementById("message-container");
                if (isGroupChat) {
                    loadGroupMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
                } else {
                    loadMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
                }
            }
        };
        picker.appendChild(btn);
    });

    document.body.appendChild(picker);
    emojiPickerTarget = messageId;

    // 外側クリックで閉じる
    setTimeout(() => {
        document.addEventListener("click", closeEmojiPicker, { once: true });
    }, 0);
}

function closeEmojiPicker() {
    const picker = document.getElementById("emoji-picker-popup");
    if (picker) picker.remove();
    emojiPickerTarget = null;
}

async function sendMessage() {
    const input = document.getElementById("msg-input");
    const content = input.value.trim();
    if (!content && !window.pendingFile) return;

    const formData = new FormData();
    if (isGroupChat) {
        formData.append("group_thread_id", currentThreadId);
    } else {
        formData.append("thread_id", currentThreadId);
    }
    formData.append("content", content);
    if (window.replyToId) formData.append("reply_to_id", window.replyToId);
    if (window.pendingFile) formData.append("attachment", window.pendingFile);

    const res = await api("send_message", "POST", formData);

    if (res && res.success) {
        input.value = "";
        cancelReply();
        cancelUpload();
        
        const container = document.getElementById("message-container");
        if (isGroupChat) {
            loadGroupMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
        } else {
            loadMessages(currentThreadId, container, {currentUserName, currentUserId}, getMessageCallbacks());
        }
    }
}

// キーボードショートカット
document.addEventListener("keydown", (e) => {
    // Alt+P: ピン留めメッセージ表示
    if (e.altKey && e.key === "p") {
        e.preventDefault();
        window.showPinnedMessages();
    }
    // Alt+Shift+?: キーボードショートカット一覧
    if (e.altKey && e.shiftKey && e.key === "?") {
        e.preventDefault();
        import('./modules/ui.js').then(m => m.showModal("keyboard-shortcuts-modal"));
    }
    // Esc: アクティブなモーダルを閉じる
    if (e.key === "Escape") {
        closeEmojiPicker();
    }
    // /: 検索フォーカス
    if (e.key === "/" && !e.ctrlKey && !e.metaKey) {
        const activeEl = document.activeElement;
        if (activeEl && (activeEl.tagName === "TEXTAREA" || activeEl.tagName === "INPUT")) return;
        e.preventDefault();
        document.getElementById("search-input")?.focus();
    }
});

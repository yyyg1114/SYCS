import { api } from './api.js';
import { showToast } from './ui.js';
import { t } from './utils.js';

const VAPID_PUBLIC_KEY = window.SYCS_CONFIG ? window.SYCS_CONFIG.vapidPublicKey : null;

export async function initNotifications() {
    if (!('Notification' in window)) {
        console.warn('This browser does not support notifications.');
        return;
    }

    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('sw.js');
            console.log('Service Worker registered with scope:', registration.scope);
            
            // If already permitted, ensure subscription is up to date
            if (Notification.permission === 'granted') {
                const alreadySubscribed = sessionStorage.getItem('sycs_pushed');
                if (!alreadySubscribed) {
                    await subscribeUserToPush(registration);
                    sessionStorage.setItem('sycs_pushed', 'true');
                }
            }
        } catch (error) {
            console.error('Service Worker registration failed:', error);
        }
    }
}

export async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        alert('This browser does not support notifications.');
        return false;
    }
    
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        const registration = await navigator.serviceWorker.ready;
        await subscribeUserToPush(registration);
        showToast(t("notification_settings", "通知設定"), t("notification_granted", "通知が許可されました"), "success");
        return true;
    } else {
        showToast(t("notification_settings", "通知設定"), t("notification_denied", "通知が拒否されました"), "warning");
        return false;
    }
}

async function subscribeUserToPush(registration) {
    if (!VAPID_PUBLIC_KEY) {
        console.warn('VAPID Public Key is missing. Push subscription skipped.');
        return;
    }
    try {
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });

        console.log('User is subscribed:', subscription);
        
        // Send subscription to backend
        await api('push_subscribe', 'POST', JSON.stringify(subscription));
    } catch (error) {
        console.error('Failed to subscribe the user: ', error);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

let unreadCount = 0;
const originalTitle = document.title;
const originalFavicon = document.querySelector('link[rel="icon"]')?.href || 'assets/img/SYCS_favicon.svg';

/**
 * Update the tab badge (title and favicon)
 */
export function updateTabBadge(count = null) {
    if (count === null) {
        unreadCount++;
    } else {
        unreadCount = count;
    }
    
    if (unreadCount > 0) {
        document.title = `(${unreadCount}) ${originalTitle}`;
        drawFaviconBadge(unreadCount);
    } else {
        document.title = originalTitle;
        resetFavicon();
    }
}

/**
 * Reset the unread count and badge
 */
export function resetTabBadge() {
    unreadCount = 0;
    document.title = originalTitle;
    resetFavicon();
}

/**
 * Show a browser notification
 */
export function showBrowserNotification(title, options = {}) {
    if (Notification.permission !== 'granted') return;
    if (document.visibilityState === 'visible' && !options.force) return;

    const defaultOptions = {
        icon: 'assets/img/SYCS_favicon.svg',
        badge: 'assets/img/SYCS_favicon.svg',
        silent: false,
    };

    const n = new Notification(title, { ...defaultOptions, ...options });
    updateTabBadge();

    n.onclick = function() {
        window.focus();
        this.close();
        resetTabBadge();
    };
}

/**
 * Manage unread states for threads (Discord-style)
 */
const unreadThreads = new Map();

export function trackUnread(threadId, count = 1) {
    if (window.SYCS_CONFIG.currentThreadId == threadId) return;
    unreadThreads.set(threadId.toString(), count);
    updateSidebarUnread(threadId, count);
    syncTabBadge();
}

export function clearUnread(threadId) {
    unreadThreads.delete(threadId.toString());
    const item = document.querySelector(`.thread-item[data-id="${threadId}"]`);
    if (item) {
        item.classList.remove('unread');
        const indicator = item.querySelector('.unread-indicator');
        if (indicator) indicator.remove();
    }
    syncTabBadge();
}

function syncTabBadge() {
    let total = 0;
    unreadThreads.forEach(count => total += count);
    updateTabBadge(total);
}

function updateSidebarUnread(threadId, count) {
    const item = document.querySelector(`.thread-item[data-id="${threadId}"]`);
    if (item) {
        item.classList.add('unread');
        let indicator = item.querySelector('.unread-indicator');
        if (!indicator) {
            indicator = document.createElement('span');
            indicator.className = 'unread-indicator';
            item.appendChild(indicator);
        }
        indicator.textContent = count > 99 ? '99+' : count;
    }
}

function drawFaviconBadge(count) {
    const img = new Image();
    img.crossOrigin = "anonymous";
    img.src = originalFavicon;
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, 32, 32);

        const radius = 8;
        const x = 24;
        const y = 8;

        ctx.beginPath();
        ctx.arc(x, y, radius + 1, 0, 2 * Math.PI);
        ctx.fillStyle = '#1e1e2e';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(x, y, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#f87171';
        ctx.fill();

        if (count > 0) {
            ctx.fillStyle = 'white';
            ctx.font = 'bold 10px Inter, Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const displayCount = count > 9 ? '9+' : count;
            ctx.fillText(displayCount, x, y + 1);
        }

        let link = document.querySelector('link[rel="icon"]');
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.href = canvas.toDataURL('image/png');
    };
}

function resetFavicon() {
    let link = document.querySelector('link[rel="icon"]');
    if (link) link.href = originalFavicon;
}

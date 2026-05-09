import { api } from './api.js';
import { showToast } from './ui.js';
import { t } from './utils.js';

const VAPID_PUBLIC_KEY = "BN1pSd_YbB6fni2gJ1jRDrPipOsYQlrSXXA6LusnqUuSIi9KRYOMAAHxR-xTKV-nNjybdxHwHoxn2HeDgN1guh8";

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
                subscribeUserToPush(registration);
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

/**
 * Show a browser notification (used when the app is open but maybe backgrounded)
 */
export function showBrowserNotification(title, options = {}) {
    if (Notification.permission !== 'granted') return;
    
    // Check if tab is focused
    if (document.visibilityState === 'visible' && !options.force) {
        return; // Don't show if user is looking at the app
    }

    const defaultOptions = {
        icon: 'assets/img/SYCS_favicon.svg',
        badge: 'assets/img/SYCS_favicon.svg',
        silent: false,
    };

    const n = new Notification(title, { ...defaultOptions, ...options });
    n.onclick = function() {
        window.focus();
        this.close();
    };
}

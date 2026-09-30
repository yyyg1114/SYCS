/**
 * SYCS UI Family Module
 * デバイスUI表現の制御・自動判定補正・手動選択の永続化
 */

import { showToast } from './ui.js';
import { t } from './utils.js';

const STORAGE_KEY = 'sycs_ui_family';

/**
 * UI Familyの初期化
 */
export function initUiFamily() {
    applyUiFamily();
}

/**
 * UI Familyの判定と適用
 */
export function applyUiFamily() {
    const root = document.documentElement;
    const userPref = localStorage.getItem(STORAGE_KEY);

    // 1. ユーザーの明示設定がある場合 ('apple', 'android', 'windows', 'generic')
    if (userPref && ['apple', 'android', 'windows', 'generic'].includes(userPref)) {
        root.dataset.uiFamily = userPref;
        updateSelectElement(userPref);
        return userPref;
    }

    // 2. ブラウザJavaScriptによる端末判定の補正
    // (例: iPadOSがMacIntelとして扱われるケースの判定補正)
    const isTouchMac = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
    if (isTouchMac) {
        root.dataset.uiFamily = 'apple';
        updateSelectElement('auto');
        return 'apple';
    }

    // 3. PHPによる初期判定値
    const initialFamily = root.dataset.uiFamily;
    if (initialFamily && ['apple', 'android', 'windows', 'generic'].includes(initialFamily)) {
        updateSelectElement('auto');
        return initialFamily;
    }

    // 4. フォールバック
    root.dataset.uiFamily = 'generic';
    updateSelectElement('auto');
    return 'generic';
}

/**
 * UI Familyを手動変更・設定する
 * @param {'auto' | 'apple' | 'android' | 'windows' | 'generic'} family 
 * @param {boolean} showToastNotify 
 */
export function setUiFamily(family, showToastNotify = true) {
    if (family === 'auto') {
        localStorage.removeItem(STORAGE_KEY);
    } else if (['apple', 'android', 'windows', 'generic'].includes(family)) {
        localStorage.setItem(STORAGE_KEY, family);
    }

    const appliedFamily = applyUiFamily();

    if (showToastNotify) {
        showToast(
            t('settings', '設定'),
            t('ui_style_changed', 'UIスタイルを変更しました'),
            'info'
        );
    }

    return appliedFamily;
}

/**
 * UI設定モーダル内のselect要素に値を反映
 */
function updateSelectElement(val) {
    const selectEl = document.getElementById('edit-ui-family-input');
    if (selectEl) {
        selectEl.value = val;
    }
}

// グローバル関数としてウィンドウに露出（HTMLインラインイベント対応）
if (typeof window !== 'undefined') {
    window.setUiFamily = setUiFamily;
}

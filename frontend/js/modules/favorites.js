/**
 * SYCS Favorites Module
 */

import { api } from './api.js';
import { t } from './utils.js';

export async function loadFavorites() {
    const list = document.getElementById("fav-thread-list");
    console.log("loadFavorites called, list element:", list);
    if (!list) {
        console.error("fav-thread-list element not found!");
        return;
    }

    list.innerHTML = `<div class="loading" style="padding: 20px; text-align: center; color: var(--text-secondary);">${t("loading", "読み込み中...")}</div>`;
    
    let favorites;
    try {
        favorites = await api("get_favorites");
        console.log("Favorites API result:", favorites);
    } catch (e) {
        console.error("Failed to fetch favorites:", e);
        list.innerHTML = `<div class="error" style="color: var(--status-offline); padding: 20px; text-align: center;">${t("error_loading_favorites", "お気に入りの読み込みに失敗しました。")}</div>`;
        return;
    }
    
    list.innerHTML = "";

    if (favorites && Array.isArray(favorites) && favorites.length > 0) {
        favorites.forEach(th => {
            const item = document.createElement("div");
            item.className = "thread-item favorite-item";
            item.style.cssText = "display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid var(--border-color); transition: background 0.2s; cursor: pointer;";
            
            // Hover effect
            item.onmouseenter = () => item.style.background = "rgba(255, 255, 255, 0.05)";
            item.onmouseleave = () => item.style.background = "transparent";
            
            const info = document.createElement("div");
            info.style.flex = "1";
            info.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="thread-icon" style="color: #f1c40f; font-size: 1.2rem;">★</span>
                    <div>
                        <div class="thread-name" style="font-weight: 600; font-size: 1.1rem; color: var(--text-primary);"># ${th.name}</div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">${th.category || 'General'}</div>
                    </div>
                </div>
            `;
            info.onclick = () => {
                location.href = `index.php?thread_id=${th.id}`;
            };
            
            const actions = document.createElement("div");
            actions.style.display = "flex";
            actions.style.gap = "8px";

            const unfavBtn = document.createElement("button");
            unfavBtn.className = "icon-btn";
            unfavBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            `;
            unfavBtn.style.color = "var(--text-secondary)";
            unfavBtn.title = t("remove_favorite", "お気に入り解除");
            unfavBtn.onclick = async (e) => {
                e.stopPropagation();
                if (confirm(t("remove_favorite_confirm", "お気に入りを解除しますか？"))) {
                    const res = await api("toggle_favorite", "POST", { thread_id: th.id });
                    if (res && res.success) {
                        loadFavorites();
                    }
                }
            };
            
            actions.appendChild(unfavBtn);
            item.appendChild(info);
            item.appendChild(actions);
            list.appendChild(item);
        });
    } else {
        list.innerHTML = `<div class="empty-state" style="padding: 60px 20px; text-align: center; color: var(--text-secondary);">
            <div style="font-size: 4rem; margin-bottom: 24px; opacity: 0.2;">⭐</div>
            <h3 style="color: var(--text-primary); margin-bottom: 8px;">${t("no_favorites_title", "お気に入りはありません")}</h3>
            <p>${t("no_favorites_desc", "スレッドの星アイコンをクリックしてお気に入りに追加しましょう。")}</p>
        </div>`;
    }
}

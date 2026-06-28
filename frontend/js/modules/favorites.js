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

    // show loading
    list.textContent = '';
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.style.cssText = 'padding: 20px; text-align: center; color: var(--text-secondary);';
    loading.textContent = t('loading', '読み込み中...');
    list.appendChild(loading);
    
    let favorites;
    try {
        favorites = await api("get_favorites");
        console.log("Favorites API result:", favorites);
    } catch (e) {
        console.error('Failed to fetch favorites:', e);
        list.textContent = '';
        const errDiv = document.createElement('div');
        errDiv.className = 'error';
        errDiv.style.cssText = 'color: var(--status-offline); padding: 20px; text-align: center;';
        errDiv.textContent = t('error_loading_favorites', 'お気に入りの読み込みに失敗しました。');
        list.appendChild(errDiv);
        return;
    }
    
    list.textContent = '';

    if (favorites && Array.isArray(favorites) && favorites.length > 0) {
        favorites.forEach(th => {
            const item = document.createElement("div");
            item.className = "thread-item favorite-item";
            item.style.cssText = "display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid var(--border-color); transition: background 0.2s; cursor: pointer;";
            
            // Hover effect
            item.onmouseenter = () => item.style.background = "rgba(255, 255, 255, 0.05)";
            item.onmouseleave = () => item.style.background = "transparent";
            
            const info = document.createElement('div');
            info.style.flex = '1';
            const infoRow = document.createElement('div');
            infoRow.style.cssText = 'display:flex; align-items:center; gap:12px;';
            const icon = document.createElement('span');
            icon.className = 'thread-icon';
            icon.style.cssText = 'color: #f1c40f; font-size: 1.2rem;';
            icon.textContent = '★';
            const infoTextWrap = document.createElement('div');
            const nameDiv = document.createElement('div');
            nameDiv.className = 'thread-name';
            nameDiv.style.cssText = 'font-weight: 600; font-size: 1.1rem; color: var(--text-primary);';
            nameDiv.textContent = '# ' + th.name;
            const catDiv = document.createElement('div');
            catDiv.style.cssText = 'font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;';
            catDiv.textContent = th.category || 'General';
            infoTextWrap.appendChild(nameDiv);
            infoTextWrap.appendChild(catDiv);
            infoRow.appendChild(icon);
            infoRow.appendChild(infoTextWrap);
            info.appendChild(infoRow);
            info.onclick = () => {
                location.href = `index.php?thread_id=${th.id}`;
            };
            
            const actions = document.createElement("div");
            actions.style.display = "flex";
            actions.style.gap = "8px";

            const unfavBtn = document.createElement('button');
            unfavBtn.className = 'icon-btn';
            // create SVG icon without innerHTML
            const svgNS = 'http://www.w3.org/2000/svg';
            const svg = document.createElementNS(svgNS, 'svg');
            svg.setAttribute('width', '18');
            svg.setAttribute('height', '18');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '2');
            svg.setAttribute('stroke-linecap', 'round');
            svg.setAttribute('stroke-linejoin', 'round');
            const line1 = document.createElementNS(svgNS, 'line');
            line1.setAttribute('x1', '18'); line1.setAttribute('y1', '6'); line1.setAttribute('x2', '6'); line1.setAttribute('y2', '18');
            const line2 = document.createElementNS(svgNS, 'line');
            line2.setAttribute('x1', '6'); line2.setAttribute('y1', '6'); line2.setAttribute('x2', '18'); line2.setAttribute('y2', '18');
            svg.appendChild(line1);
            svg.appendChild(line2);
            unfavBtn.appendChild(svg);
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
        }
    } else {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.style.cssText = 'padding: 60px 20px; text-align: center; color: var(--text-secondary);';
        const star = document.createElement('div');
        star.style.cssText = 'font-size:4rem; margin-bottom:24px; opacity:0.2;';
        star.textContent = '⭐';
        const h3 = document.createElement('h3');
        h3.style.cssText = 'color: var(--text-primary); margin-bottom:8px;';
        h3.textContent = t('no_favorites_title', 'お気に入りはありません');
        const p = document.createElement('p');
        p.textContent = t('no_favorites_desc', 'スレッドの星アイコンをクリックしてお気に入りに追加しましょう。');
        empty.appendChild(star);
        empty.appendChild(h3);
        empty.appendChild(p);
        list.appendChild(empty);
    }
}

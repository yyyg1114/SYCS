/**
 * SYCS Profile Management Module
 */

import { api } from './api.js';
import { showToast } from './ui.js';
import { t } from './utils.js';

const PERSIST_PREFIX = 'sycs_profile_';
const PERSIST_KEYS = [
  'edit-banner-input',
  'edit-layout-input',
  'edit-twitter-input',
  'edit-accent-input',
  'edit-github-input',
  'edit-bio-input',
  'edit-keywords-input',
  'modal-status-input',
  'sycs_theme'
];

/**
 * Save a single input value to localStorage
 * @param {string} id 
 * @param {string} value 
 */
export function persistProfileInput(id, value) {
  localStorage.setItem(PERSIST_PREFIX + id, value);
}

/**
 * Load all persisted values from localStorage and apply them to the UI
 */
export function loadPersistedProfileInputs() {
  PERSIST_KEYS.forEach(id => {
    const value = localStorage.getItem(PERSIST_PREFIX + id);
    if (value !== null) {
      const input = document.getElementById(id);
      if (input) {
        input.value = value;
      }
      // Trigger previews
      if (id === 'edit-banner-input') updatePreviewBanner(value);
      if (id === 'edit-layout-input') updatePreviewLayout(value);
      if (id === 'edit-accent-input') updateAccentColor(value);
      if (id === 'edit-bio-input') updatePreviewBio(value);
      if (id === 'modal-status-input') updatePreviewStatus(value);
      if (id === 'sycs_theme') setTheme(value);
    }
  });
}

/**
 * Clear all persisted profile values from localStorage
 */
export function clearPersistedProfileInputs() {
  PERSIST_KEYS.forEach(id => {
    localStorage.removeItem(PERSIST_PREFIX + id);
  });
}

/**
 * Set theme and persist it
 * @param {string} theme 
 */
export function setTheme(theme) {
  import('./ui.js').then(m => m.setTheme(theme, false)); // false = don't show toast for preview
  persistProfileInput('sycs_theme', theme);
}

/**
 * Preview avatar before upload
 * @param {HTMLInputElement} input 
 */
export function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const container = document.getElementById("preview-avatar-container");
      if (container) {
        container.innerHTML = `<img src="${e.target.result}" class="discord-avatar" id="preview-avatar-img">`;
      }
      const removeBtn = document.getElementById("btn-remove-avatar");
      if (removeBtn) removeBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

/**
 * Remove avatar preview
 */
export function removeAvatarPreview() {
  const container = document.getElementById("preview-avatar-container");
  if (container) {
    const userName = window.SYCS_CONFIG.currentUserName;
    container.innerHTML = userName ? userName.substring(0, 1).toUpperCase() : "?";
  }
  const input = document.getElementById("edit-avatar-input");
  if (input) input.value = "";
  const removeBtn = document.getElementById("btn-remove-avatar");
  if (removeBtn) removeBtn.style.display = "none";
}

/**
 * Update banner color preview
 * @param {string} color 
 */
export function updatePreviewBanner(color) {
  const banner = document.getElementById("preview-banner");
  if (banner) {
    banner.style.background = color;
  }
  persistProfileInput('edit-banner-input', color);
}

/**
 * Preview banner image before upload
 * @param {HTMLInputElement} input 
 */
export function previewBannerImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const banner = document.getElementById("preview-banner");
      if (banner) {
        banner.style.background = `url('${e.target.result}') center/cover`;
      }
      const removeBtn = document.getElementById("btn-remove-banner");
      if (removeBtn) removeBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

/**
 * Remove banner image preview
 */
export function removeBannerPreview() {
  const colorInput = document.getElementById("edit-banner-input");
  const color = colorInput ? colorInput.value : "#6366f1";
  updatePreviewBanner(color);
  const input = document.getElementById("edit-banner-img-input");
  if (input) input.value = "";
  const removeBtn = document.getElementById("btn-remove-banner");
  if (removeBtn) removeBtn.style.display = "none";
}

/**
 * Update layout preview
 * @param {string} layout 
 */
export function updatePreviewLayout(layout) {
  const card = document.getElementById("profile-preview-card");
  if (card) {
    card.dataset.layout = layout;
  }
  persistProfileInput('edit-layout-input', layout);
}

/**
 * Update accent color preview
 * @param {string} color 
 */
export function updateAccentColor(color) {
  document.documentElement.style.setProperty("--accent-color", color);
  document.documentElement.style.setProperty("--accent-hover", color + "dd");
  persistProfileInput('edit-accent-input', color);
}

/**
 * Update bio preview
 * @param {string} bio 
 */
export function updatePreviewBio(bio) {
  const preview = document.getElementById("preview-bio");
  if (preview) {
    preview.innerText = bio;
  }
  persistProfileInput('edit-bio-input', bio);
}

/**
 * Update status preview
 * @param {string} status 
 */
export function updatePreviewStatus(status) {
  const indicator = document.getElementById("preview-status-indicator");
  if (indicator) {
    indicator.className = `discord-status-indicator status-${status}`;
  }
  persistProfileInput('modal-status-input', status);
}

/**
 * Save profile changes
 */
export async function saveProfile() {
  const formData = new FormData();
  
  const avatarInput = document.getElementById("edit-avatar-input");
  if (avatarInput.files[0]) formData.append("avatar", avatarInput.files[0]);
  
  const bannerImgInput = document.getElementById("edit-banner-img-input");
  if (bannerImgInput.files[0]) formData.append("banner_img", bannerImgInput.files[0]);
  
  formData.append("banner_color", document.getElementById("edit-banner-input").value);
  formData.append("bio", document.getElementById("edit-bio-input").value);
  formData.append("status", document.getElementById("modal-status-input").value);
  formData.append("profile_layout", document.getElementById("edit-layout-input").value);
  
  const social = {
    twitter: document.getElementById("edit-twitter-input").value,
    github: document.getElementById("edit-github-input").value
  };
  formData.append("social_links", JSON.stringify(social));
  formData.append("notification_keywords", document.getElementById("edit-keywords-input").value);

  const pendingTheme = localStorage.getItem(PERSIST_PREFIX + 'sycs_theme');
  if (pendingTheme) {
    formData.append("theme_preference", JSON.stringify({ theme: pendingTheme }));
  }

  const res = await api("update_profile", "POST", formData);
  if (res && res.success) {
    if (pendingTheme) {
      localStorage.setItem('sycs_theme', pendingTheme);
    }
    clearPersistedProfileInputs();
    showToast(t("success", "成功"), t("profile_updated", "プロフィールを更新しました"), "success");
    location.reload();
  } else {
    showToast(t("error", "エラー"), res.error || t("failed_to_update", "更新に失敗しました"), "error");
  }
}

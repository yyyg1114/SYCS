<?php

/**
 * SYCS Modals Include
 */
?>

<!-- Group Creation Modal -->
<dialog id="group-creation-modal" class="modal"
    style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
    <h3><?= __('create_group') ?></h3>
    <input type="text" id="group-chat-name" class="chat-input" placeholder="<?= __('enter_group_name') ?>" style="width:100%; margin-bottom:10px;">
    <p><?= __('select_members') ?></p>
    <div id="group-member-picker" style="max-height:200px; overflow-y:auto; margin-bottom:15px; border:1px solid var(--border-color); border-radius:4px; padding:5px;"></div>
    <div style="display:flex; gap:10px;">
        <button class="btn-secondary" onclick="document.getElementById('group-creation-modal').close()"><?= __('cancel') ?></button>
        <button class="btn-primary" onclick="submitGroupCreation()"><?= __('create') ?></button>
    </div>
</dialog>

<!-- User Profile Edit Modal -->
<dialog id="profile-modal" class="profile-modal">
    <div class="profile-content">
        <div class="profile-edit-pane">
            <h2 style="margin-bottom:24px; font-size:1.5rem; font-weight:700;"><?= __('user_settings') ?></h2>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('avatar') ?></label>
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-primary" onclick="document.getElementById('edit-avatar-input').click()" style="width:auto; padding:8px 16px;">
                        <?= __('change_avatar') ?>
                    </button>
                    <button class="btn-secondary" id="btn-remove-avatar" onclick="removeAvatarPreview()" style="width:auto; background:#333; display:none;">
                        <?= __('remove') ?>
                    </button>
                    <input type="file" id="edit-avatar-input" hidden accept="image/*" onchange="previewAvatar(this)">
                </div>
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('banner') ?></label>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="color" id="edit-banner-input" class="modal-input" style="width:60px; height:40px; padding:2px;"
                            oninput="updatePreviewBanner(this.value)" value="<?= htmlspecialchars($currentUserBanner) ?>">
                        <button class="btn-primary" onclick="document.getElementById('edit-banner-img-input').click()" style="width:auto; padding:8px 16px;">
                            <?= __('upload_image') ?>
                        </button>
                        <button class="btn-secondary" id="btn-remove-banner" onclick="removeBannerPreview()" style="width:auto; background:#333; display:none;">
                            <?= __('remove') ?>
                        </button>
                        <input type="file" id="edit-banner-img-input" hidden accept="image/*" onchange="previewBannerImage(this)">
                    </div>
                </div>
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('profile_layout') ?></label>
                <select id="edit-layout-input" class="modal-input" onchange="updatePreviewLayout(this.value)">
                    <option value="classic" <?= $currentUserProfileLayout === 'classic' ? 'selected' : '' ?>><?= __('layout_classic') ?></option>
                    <option value="modern" <?= $currentUserProfileLayout === 'modern' ? 'selected' : '' ?>><?= __('layout_modern') ?></option>
                    <option value="compact" <?= $currentUserProfileLayout === 'compact' ? 'selected' : '' ?>><?= __('layout_compact') ?></option>
                </select>
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Twitter</label>
                <input type="text" id="edit-twitter-input" class="modal-input" placeholder="@username"
                    value="<?= htmlspecialchars($currentUserSocialLinks['twitter'] ?? '') ?>">
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('theme_settings') ?></label>
                <div style="display:flex; gap:10px;">
                    <button class="btn-secondary" onclick="setTheme('dark')" style="flex:1;"><?= __('dark') ?></button>
                    <button class="btn-secondary" onclick="setTheme('light')" style="flex:1;"><?= __('light') ?></button>
                </div>
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('accent_color') ?></label>
                <input type="color" id="edit-accent-input" class="modal-input" style="height: 40px; padding: 5px;"
                    oninput="updateAccentColor(this.value)" value="#6366f1">
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('github_username') ?></label>
                <input type="text" id="edit-github-input" class="modal-input" placeholder="example_git"
                    value="<?= htmlspecialchars($currentUserSocialLinks['github'] ?? '') ?>">
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('bio') ?></label>
                <textarea id="edit-bio-input" class="modal-textarea" placeholder="<?= __('bio_placeholder') ?>"
                    oninput="updatePreviewBio(this.value)"><?= htmlspecialchars($currentUserBio) ?></textarea>
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('notification_keywords') ?></label>
                <input type="text" id="edit-keywords-input" class="modal-input" placeholder="<?= __('notification_keywords_placeholder') ?>"
                    value="<?= htmlspecialchars($currentUserData['notification_keywords'] ?? '') ?>">
                <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:5px;">
                    <?= __('notification_keywords_desc') ?>
                </p>
            </div>

            <div class="modal-form-group">
                <label class="modal-label"><?= __('status') ?></label>
                <select id="modal-status-input" class="modal-input" onchange="updatePreviewStatus(this.value)">
                    <option value="online" <?= $currentUserStatus === 'online' ? 'selected' : '' ?>><?= __('status_online') ?></option>
                    <option value="busy" <?= $currentUserStatus === 'busy' ? 'selected' : '' ?>><?= __('status_busy') ?></option>
                    <option value="not_allowed" <?= $currentUserStatus === 'not_allowed' ? 'selected' : '' ?>><?= __('status_not_allowed') ?></option>
                    <option value="step_out" <?= $currentUserStatus === 'step_out' ? 'selected' : '' ?>><?= __('status_step_out') ?></option>
                    <option value="away" <?= $currentUserStatus === 'away' ? 'selected' : '' ?>><?= __('status_away') ?></option>
                    <option value="offline" <?= $currentUserStatus === 'offline' ? 'selected' : '' ?>><?= __('status_offline') ?></option>
                    <option value="going_away" <?= $currentUserStatus === 'going_away' ? 'selected' : '' ?>><?= __('status_going_away') ?></option>
                </select>
            </div>

            <div style="margin-top:32px; display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button class="btn-secondary" onclick="document.getElementById('profile-modal').close()" style="padding: 12px; flex: 1;"><?= __('cancel') ?></button>
                    <button class="btn-primary" onclick="saveProfile()" style="padding: 12px; flex: 1; font-weight: 600;"><?= __('save') ?></button>
                </div>
                <div style="display:flex; justify-content: flex-end;">
                    <a href="delete_account.php" style="color:#f87171; font-size:0.8rem; text-decoration:none;"><?= __('delete_account') ?></a>
                </div>
            </div>
        </div>

        <div class="profile-preview-pane">
            <div class="discord-card" id="profile-preview-card" data-layout="<?= htmlspecialchars($currentUserProfileLayout) ?>">
                <div class="discord-banner" id="preview-banner" style="background: <?= $currentUserBannerUrl ? "url('" . htmlspecialchars($currentUserBannerUrl) . "') center/cover" : htmlspecialchars($currentUserBanner) ?>"></div>
                <div class="discord-avatar-wrapper">
                    <div class="discord-avatar" id="preview-avatar-container">
                        <?php if ($currentUserAvatar): ?>
                            <img src="<?= htmlspecialchars($currentUserAvatar) ?>" class="discord-avatar" id="preview-avatar-img">
                        <?php else: ?>
                            <?= strtoupper(substr($currentUser, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="discord-status-indicator status-<?= htmlspecialchars($currentUserStatus) ?>" id="preview-status-indicator"></div>
                </div>
                <div class="discord-body">
                    <div class="discord-username"><?= htmlspecialchars($currentUser) ?></div>
                    <div class="discord-custom-status" id="preview-custom-status-text"></div>
                    <div class="discord-divider"></div>
                    <div class="discord-section-title"><?= __('bio') ?></div>
                    <div class="discord-bio" id="preview-bio"><?= nl2br(htmlspecialchars($currentUserBio)) ?></div>
                    <div class="discord-divider"></div>
                    <section class="section2" id="gps-section">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div class="discord-section-title" style="margin:0; display:flex; align-items:center;">
                                GPS Status
                                <div id="gps-header-status" class="gps-status-indicator"></div>
                            </div>
                            <button class="icon-btn" onclick="if(typeof locationManager !== 'undefined') locationManager.getCurrentLocation()" title="GPS更新" style="padding:2px; opacity:0.6;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 4v6h-6"></path>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="gps-status" class="gps-status-target" style="min-height:20px; font-size:0.8rem; color:var(--text-secondary);"><?= __('gps_waiting') ?></div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</dialog>

<!-- User Profile View Modal -->
<dialog id="user-profile-modal" class="profile-modal">
    <div class="profile-content" style="max-width: 450px;">
        <div class="profile-preview-pane" style="width: 100%;">
            <div class="discord-card" id="user-profile-card">
                <div class="discord-banner" id="user-profile-banner"></div>
                <div class="discord-avatar-wrapper">
                    <div class="discord-avatar" id="user-profile-avatar-container"></div>
                    <div class="discord-status-indicator" id="user-profile-status-indicator"></div>
                </div>
                <div class="discord-body">
                    <div class="discord-username" id="user-profile-username"></div>
                    <div class="discord-custom-status" id="user-profile-custom-status"></div>
                    <div class="discord-divider"></div>
                    <div class="discord-section-title"><?= __('bio') ?></div>
                    <div class="discord-bio" id="user-profile-bio"></div>
                    <div class="discord-divider"></div>
                    <div class="discord-section-title">SNS</div>
                    <div id="user-profile-sns" style="display:flex; gap:10px; margin-top:8px;"></div>
                </div>
            </div>
            <div style="margin-top: 16px; display: flex; gap: 8px; margin-left: 15px;">
                <button class="btn-primary" onclick="document.getElementById('user-profile-modal').close()" style="flex: 1;"><?= __('close') ?></button>
                <button class="btn-primary" id="user-profile-dm-btn" style="flex: 1;"><?= __('send_dm') ?></button>
            </div>
        </div>
    </div>
</dialog>

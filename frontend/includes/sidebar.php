<?php

/**
 * SYCS Shared Sidebar
 * This file is included in index.php and dm.php
 */
?>
<aside id="main-sidebar" class="sidebar">
    <div class="sidebar-top">
        <div class="logo-container">
            <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
            <span class="logo-version" style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v2.2.3</span>
        </div>
        <div class="sidebar-secondary">
            <div class="release-notes">
                <a href="../release_notes/release_notes.php" target="_blank" style="font-size: 0.8rem; margin-left: 100px; align-items: end; text-decoration: none; color: var(--text-primary); background-color: var(--accent-hover); border-radius: 4px; padding: 2px 4px;"><?= __('release_notes') ?></a>
            </div>
        </div>
        <nav>
            <ul class="nav-list">
                <li class="nav-item <?= $currentPage === 'threads' ? 'active' : '' ?>" data-tab="threads" onclick="location.href='index.php'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="9" x2="20" y2="9" />
                        <line x1="4" y1="15" x2="20" y2="15" />
                        <line x1="10" y1="3" x2="8" y2="21" />
                        <line x1="16" y1="3" x2="14" y2="21" />
                    </svg>
                    <span><?= __('threads') ?></span>
                </li>
                <li class="nav-item <?= $currentPage === 'dm' ? 'active' : '' ?>" data-tab="dm" onclick="location.href='dm.php'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span><?= __('dm') ?></span>
                    <span id="dm-unread-badge" style="display:none; background:#ef4444; color:white; border-radius:9999px; font-size:0.65rem; font-weight:700; padding:1px 6px; margin-left:6px; min-width:18px; text-align:center;"></span>
                </li>
                <li class="nav-item" data-tab="favorites">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                    <span><?= __('favorites') ?></span>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Sidebar Widgets -->
    <div class="sidebar-widgets">
        <div class="widget-tabs">
            <button class="widget-tab active" data-widget="clock" title="<?= __('clock') ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </button>
            <button class="widget-tab" data-widget="notepad" title="<?= __('notepad') ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </button>
            <button class="widget-tab" data-widget="filer" title="<?= __('filer') ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            </button>
            <button class="widget-tab" data-widget="todo" title="<?= __('todo') ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </button>
        </div>
        <div class="widget-content">
            <div id="widget-clock" class="widget-pane active">
                <div class="clock-display">
                    <div id="analog-clock" class="analog-clock">
                        <div class="clock-face">
                            <div class="sub-dial sub-9">
                                <div class="sub-hand"></div><span class="sub-label">24H</span>
                                <div class="sub-center-dot"></div>
                            </div>
                            <div class="sub-dial sub-3">
                                <div class="sub-hand"></div><span class="sub-label">DAY</span>
                                <div class="sub-center-dot"></div>
                            </div>
                            <div class="sub-dial sub-6">
                                <div class="sub-hand"></div><span class="sub-label">SEC</span>
                                <div class="sub-center-dot"></div>
                            </div>
                            <div class="date-window"><span><?= date('j') ?></span></div>
                            <img src="./assets/img/SYCS_Logo.svg" alt="Logo" class="clock-logo">
                            <div class="hand hour-hand"></div>
                            <div class="hand minute-hand"></div>
                            <div class="hand second-hand"></div>
                            <div class="center-dot"></div>
                        </div>
                    </div>
                    <div id="digital-clock" class="digital-clock" style="display:none;">00:00:00</div>
                </div>
                <div class="clock-controls">
                    <label class="switch-label">
                        <span><?= __('digital') ?></span>
                        <div class="switch">
                            <input type="checkbox" id="clock-type-toggle" checked>
                            <span class="slider"></span>
                        </div>
                        <span><?= __('analog') ?></span>
                    </label>
                </div>
            </div>
            <div id="widget-notepad" class="widget-pane">
                <textarea id="notepad-area" placeholder="<?= __('notepad_placeholder') ?>"></textarea>
            </div>
            <div id="widget-filer" class="widget-pane">
                <div id="file-list" class="file-list">
                    <div class="loading"><?= __('loading') ?></div>
                </div>
            </div>
            <div id="widget-todo" class="widget-pane">
                <div class="todo-container">
                    <div class="todo-input-area">
                        <input type="text" id="todo-input" placeholder="<?= __('task_placeholder') ?>">
                        <button class="todo-add-btn" onclick="addTodo()" title="<?= __('add') ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                    <div id="todo-list" class="todo-list"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-bottom">
        <div class="user-block">
            <div class="user-info-row">
                <div class="avatar-container">
                    <div class="avatar" id="global-user-avatar">
                        <?php if ($currentUserAvatar): ?>
                            <img src="<?= htmlspecialchars($currentUserAvatar) ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <?= htmlspecialchars(mb_substr($currentUser, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="status-indicator status-<?= htmlspecialchars($currentUserStatus) ?>" id="global-status-indicator"></div>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($currentUser) ?></span>
                    <div class="status-select-container">
                        <select id="sidebar-status-input" class="status-select" onchange="updateMyStatus(this.value)">
                            <option value="online" <?= $currentUserStatus === 'online' ? 'selected' : '' ?>><?= __('status_online') ?></option>
                            <option value="busy" <?= $currentUserStatus === 'busy' ? 'selected' : '' ?>><?= __('status_busy') ?></option>
                            <option value="not_allowed" <?= $currentUserStatus === 'not_allowed' ? 'selected' : '' ?>><?= __('status_not_allowed') ?></option>
                            <option value="step_out" <?= $currentUserStatus === 'step_out' ? 'selected' : '' ?>><?= __('status_step_out') ?></option>
                            <option value="away" <?= $currentUserStatus === 'away' ? 'selected' : '' ?>><?= __('status_away') ?></option>
                            <option value="offline" <?= $currentUserStatus === 'offline' ? 'selected' : '' ?>><?= __('status_offline') ?></option>
                            <option value="going_away" <?= $currentUserStatus === 'going_away' ? 'selected' : '' ?>><?= __('status_going_away') ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="sidebar-actions">
            <a href="javascript:void(0)" onclick="showProfileModal()" class="action-link"><?= __('settings') ?></a>
            <div class="lang-switcher">
                <select class="status-select" onchange="changeLang(this.value)">
                    <option value="ja" <?= I18n::getInstance()->getCurrentLang() === 'ja' ? 'selected' : '' ?>><?= __('lang_ja') ?></option>
                    <option value="en" <?= I18n::getInstance()->getCurrentLang() === 'en' ? 'selected' : '' ?>><?= __('lang_en') ?></option>
                    <option value="zh" <?= I18n::getInstance()->getCurrentLang() === 'zh' ? 'selected' : '' ?>><?= __('lang_zh') ?></option>
                </select>
            </div>
            <a href="?logout=1" class="action-link" style="color:#ef4444;"><?= __('logout') ?></a>
        </div>
    </div>
</aside>

<?php

/**
 * Unified App Header Component
 * Variables:
 * - $headerTitle: string (Title of the page/thread)
 * - $headerIcon: string (HTML or emoji icon)
 * - $headerActions: string (HTML for action buttons)
 * - $headerSearch: bool (Whether to show the search bar)
 * - $isThread: bool (Whether this is a thread chat view)
 */
?>
<header class="chat-header">
    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
        <?php if (isset($backAction)): ?>
            <button class="icon-btn" onclick="<?= $backAction ?>" title="<?= __('back') ?>" style="margin-right:10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
            </button>
        <?php else: ?>
            <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="<?= __('menu') ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        <?php endif; ?>

        <?php if ($isThread ?? false): ?>
            <div class="thread-name-clickable" onclick="toggleThreadBrowser()">
                <button id="fav-btn" class="icon-btn" onclick="event.stopPropagation(); toggleFavorite()"
                    title="<?= __('favorites') ?>">
                    ☆
                </button>
                <span id="current-thread-name"><?= htmlspecialchars($currentThreadName ?? __('general')) ?></span>
                <svg class="dropdown-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        <?php else: ?>
            <div class="thread-info" style="display: flex; align-items: center; gap: 10px;">
                <?php if (isset($headerIcon)): ?>
                    <span class="thread-icon" id="current-header-icon" style="font-size: 1.2rem;"><?= $headerIcon ?></span>
                <?php endif; ?>
                <h3 class="thread-name" id="current-header-title" style="margin: 0; font-size: 1.1rem; font-weight: 600;"><?= $headerTitle ?? '' ?></h3>
            </div>
        <?php endif; ?>
    </div>

    <div class="thread-actions" style="display: flex; align-items: center; gap: 8px;">
        <?php if ($headerSearch ?? false): ?>
            <div class="search-input-wrapper">
                <input type="text" id="search-input" placeholder="<?= __('search_placeholder') ?>" onkeydown="if(event.key==='Enter') searchMessages()">
                <button class="icon-btn" onclick="toggleAdvancedSearch()" title="<?= __('search_filter') ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </button>
                <div id="advanced-search-panel" class="advanced-search-panel">
                    <!-- Advanced search content -->
                    <div class="form-group-inline">
                        <label><input type="checkbox" id="search-has-attachment"> <?= __('has_attachment') ?></label>
                    </div>
                    <div class="form-group-stack">
                        <div class="label-mini"><?= __('date_from') ?></div>
                        <input type="date" id="search-date-from" class="mini-input">
                    </div>
                    <div class="form-group-stack">
                        <div class="label-mini"><?= __('date_to') ?></div>
                        <input type="date" id="search-date-to" class="mini-input">
                    </div>
                    <button class="btn-primary" onclick="searchMessages(); toggleAdvancedSearch();"><?= __('search') ?></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($headerActions)): ?>
            <?= $headerActions ?>
        <?php endif; ?>
    </div>
</header>

<header class="chat-header">
    <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="<?= __('menu') ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <div class="thread-name-clickable" onclick="toggleThreadBrowser()">
        <button id="fav-btn" class="icon-btn" onclick="event.stopPropagation(); toggleFavorite()"
            title="<?= __('favorites') ?>">
            ☆
        </button>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" y1="9" x2="20" y2="9" />
            <line x1="4" y1="15" x2="20" y2="15" />
            <line x1="10" y1="3" x2="8" y2="21" />
            <line x1="16" y1="3" x2="14" y2="21" />
        </svg>
        <span id="current-thread-name"><?= htmlspecialchars($currentThreadName ?? __('general')) ?></span>
        <svg class="dropdown-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9" />
        </svg>
    </div>
    <div class="thread-actions" id="thread-actions-block">
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
            <button class="icon-btn" onclick="searchMessages()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="24" y1="24" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            <div id="advanced-search-panel" class="advanced-search-panel">
                <div class="form-group-inline">
                    <label>
                        <input type="checkbox" id="search-has-attachment"> <?= __('has_attachment') ?>
                    </label>
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
        <button id="mute-btn" class="icon-btn" onclick="toggleMute()" title="<?= __('mute_notifications') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </button>
        <button class="icon-btn" onclick="showAttachmentGallery()" title="<?= __('attachment_list') ?>">
            <img src="assets/img/files.svg" alt="<?= __('gallery') ?>" class="action-icon">
        </button>
        <button class="icon-btn" onclick="showPinnedMessages()" title="<?= __('pinned_messages_list') ?>">
            <img src="assets/img/pin.svg" alt="<?= __('pinned_messages_list') ?>" class="action-icon">
        </button>
        <button class="icon-btn" onclick="editCurrentThread()" title="<?= __('edit') ?>">
            <img src="assets/img/edit.svg" alt="<?= __('edit') ?>" class="action-icon">
        </button>
        <button class="icon-btn btn-danger" onclick="deleteCurrentThread()" title="<?= __('delete') ?>">
            <img src="assets/img/trash.svg" alt="<?= __('delete') ?>" class="action-icon">
        </button>
    </div>
</header>

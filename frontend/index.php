<?php
// v1.2.38

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Secure Session Settings
require_once __DIR__ . '/../backend/session_config.php';

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
require_once __DIR__ . '/../backend/I18n.php';

// 1.5 Initialize Internationalization
I18n::getInstance();

// 2. HTTP Security Headers
SecurityUtil::sendSecurityHeaders();


// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Database Initialization & Migrations
require_once __DIR__ . '/../backend/db_init.php';
db_init($mysqli);

// 4. API Logic (Refactored to external handler)
if (isset($_GET['api'])) {
    require_once __DIR__ . '/../backend/api/Handler.php';
    require_once __DIR__ . '/../backend/helpers.php';
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $handler = new ApiHandler($mysqli, $userId, $_SESSION['csrf_token'] ?? null);
    $handler->handle($_GET['api']);
}

// --- Auth Status Check ---
$isLoggedIn = isset($_SESSION['user']);

$currentUserStatus = 'online';
$currentUserCustomStatus = '';
$currentUserBio = '';
$currentUserAvatar = '';
$currentUserBanner = '#6366f1';
$currentUserBannerUrl = '';
$currentUserProfileLayout = 'classic';
$currentUserSocialLinks = [];
$currentUserThemePref = [];
$currentUserKeywords = '';
$currentThreadCreatorId = 0;

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];
$initialThreadId = $_SESSION['last_thread_id'] ?? 1;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($isLoggedIn) {
    $stmt = $mysqli->prepare("SELECT last_thread_id, status, custom_status, bio, avatar_url, banner_color, banner_url, profile_layout, social_links, theme_preference, notification_keywords FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $initialThreadId = $row['last_thread_id'] ?: 1;
        $_SESSION['last_thread_id'] = $initialThreadId;
        $currentUserStatus = $row['status'] ?: 'online';
        $currentUserCustomStatus = $row['custom_status'];
        $currentUserBio = $row['bio'];
        $currentUserAvatar = $row['avatar_url'];
        $currentUserBanner = $row['banner_color'] ?: '#6366f1';
        $currentUserBannerUrl = $row['banner_url'];
        $currentUserProfileLayout = $row['profile_layout'] ?: 'classic';
        $currentUserSocialLinks = json_decode($row['social_links'] ?: '{}', true);
        $currentUserThemePref = json_decode($row['theme_preference'] ?: '{}', true);
        $currentUserKeywords = $row['notification_keywords'] ?: '';
    }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT * FROM threads WHERE id = ?");
    $stmt->bind_param("i", $initialThreadId);
    $stmt->execute();
    $tres = $stmt->get_result();
    $threadRow = $tres->fetch_assoc();
    $currentThreadName = $threadRow ? $threadRow['name'] : 'general';
    $currentThreadCreatorId = $threadRow ? $threadRow['creator_id'] : 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::getInstance()->getCurrentLang() ?>">

<head>
    <meta charset="UTF-8">
    <title>SYCS - Shinjuku Yamabuki Chat System</title>
    <meta name="description" content="SYCS - <?= __('release_notes_desc') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SYCS">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="SYCS">
    <meta name="msapplication-TileColor" content="#1a1a2e">
    <meta name="msapplication-navbutton-color" content="#6366f1">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/SYCS_favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="stylesheet" href="css/indicators.css">
    <link rel="stylesheet" href="css/markdown.css">
    <link rel="stylesheet" href="css/map.css">
    <link rel="stylesheet" href="css/widgets.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- highlight.js for Syntax Highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <?php include __DIR__ . '/includes/config.php'; ?>
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <aside id="main-sidebar" class="sidebar">
            <div class="sidebar-top">
                <div class="logo-container">
                    <img src="./assets/img/SYCS_Logo.svg" alt="SYCS_Logo" class="logo">
                    <span class="logo-version" style="font-size: 0.8rem; margin-left: 10px; align-items: end;">v1.2.38</span>
                </div>
                <div class="sidebar-secondary">
                    <div class="release-notes">
                        <a href="../release_notes/release_notes.php" target="_blank" style="font-size: 0.8rem; margin-left: 100px; align-items: end; text-decoration: none; color: var(--text-primary); background-color: var(--accent-hover); border-radius: 4px; padding: 2px 4px;"><?= __('release_notes') ?></a>
                    </div>
                </div>
                <nav>
                    <ul class="nav-list">
                        <li class="nav-item active" data-tab="threads">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="9" x2="20" y2="9" />
                                <line x1="4" y1="15" x2="20" y2="15" />
                                <line x1="10" y1="3" x2="8" y2="21" />
                                <line x1="16" y1="3" x2="14" y2="21" />
                            </svg>
                            <span><?= __('threads') ?></span>
                        </li>
                        <li class="nav-item" data-tab="dm">
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
                        <li class="nav-item" onclick="window.open('meetings.php', '_blank', 'noopener noreferrer')" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px; display: none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                            <span style="font-weight: 600;"><?= __('meeting') ?></span>
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
                                    <div class="date-window"><span>19</span></div>
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
                                <input type="text" id="todo-input" placeholder="<?= __('task_placeholder', 'タスクを入力...') ?>">
                                <button class="todo-add-btn" onclick="addTodo()" title="<?= __('add', '追加') ?>">
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

        <main class="main-content">
            <section id="threads-pane" class="content-pane active">
                <div class="chat-area">
                    <?php include 'includes/chat_header.php'; ?>
                    <div class="search-results-overlay" id="search-results-overlay">
                        <div class="search-results-header">
                            <span><?= __('search_results') ?></span>
                            <span class="close-btn" onclick="toggleSearch(false)">✕</span>
                        </div>
                        <div id="search-results-list" class="search-results-list"></div>
                    </div>
                    <div id="message-container" class="chat-messages"></div>
                    <div class="drag-overlay"><?= __('drop_to_upload') ?></div>

                    <div id="reply-bar" class="reply-bar">
                        <div style="display: flex; flex-direction: column; gap: 2px; overflow: hidden;">
                            <span style="font-size: 0.75rem; opacity: 0.8;"><?= __('replying_to') ?> <strong id="reply-target-name">User</strong></span>
                            <div id="reply-preview-text" style="font-size: 0.8rem; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; opacity: 0.6;">...</div>
                        </div>
                        <span class="close-btn" onclick="cancelReply()">✕</span>
                    </div>
                    <div id="upload-preview" class="upload-preview">
                        <span style="font-size:0.85rem; color:var(--text-secondary);"><?= __('attachments') ?> </span>
                        <div id="preview-content"></div>
                        <span class="close-btn upload-cancel" onclick="cancelUpload()">✕</span>
                    </div>

                    <div id="pwa-install-banner-threads" class="pwa-install-banner-integrated" style="display:none;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:1.2rem;">📱</span>
                            <span style="font-weight:600;"><?= __('install_sycs') ?></span>
                        </div>
                        <button class="btn-add" onclick="installPWA()"><?= __('install') ?></button>
                        <button class="btn-close" onclick="dismissInstallBanner()">✕</button>
                    </div>

                    <div id="typing-indicator" class="typing-indicator-bar" style="font-size: 0.75rem; color: var(--text-secondary); margin: 0 16px; min-height: 18px;"></div>

                    <div class="chat-input-area">
                        <div class="input-wrapper">
                            <button class="upload-btn-plus" title="<?= __('upload') ?>" onclick="openMediaUploadModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <textarea id="msg-input" class="chat-input" placeholder="<?= __('send_message_placeholder') ?>"
                                rows="1" onkeydown="handleInputKey(event)" oninput="handleTyping()"></textarea>
                            <select id="self-destruct-timer" title="<?= __('auto_delete') ?>">
                                <option value="0"><?= __('no_expiry') ?></option>
                                <option value="60"><?= __('one_minute') ?></option>
                                <option value="3600"><?= __('one_hour') ?></option>
                                <option value="86400"><?= __('one_day') ?></option>
                            </select>
                            <button class="send-btn-modern" onclick="sendMessage()">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13" />
                                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <aside id="thread-browser" class="thread-browser">
                    <div class="panel-header">
                        <span><?= __('sidebar') ?></span>
                        <div class="close-btn" onclick="toggleThreadBrowser()"><svg width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg></div>
                    </div>

                    <div class="sidebar-tabs">
                        <button class="tab-btn active" onclick="switchSidebarTab('threads')"><?= __('threads') ?></button>
                        <button class="tab-btn" onclick="switchSidebarTab('groups')"><?= __('groups') ?></button>
                    </div>

                    <div id="thread-list" class="thread-list"></div>
                    <div id="group-list" class="thread-list" style="display:none;"></div>

                    <div id="create-thread-area" class="create-thread-area">
                        <input type="text" id="new-thread-name" class="create-input" placeholder="<?= __('new_thread_name') ?>">
                        <button onclick="createThread()" class="btn-primary" style="width: 100%;"><?= __('create') ?></button>
                    </div>

                    <div id="create-group-area" class="create-thread-area" style="display:none;">
                        <button onclick="showGroupCreationDialog()" class="btn-primary" style="width: 100%;"><?= __('create_group') ?></button>
                    </div>

                    <div id="online-users-section" class="sidebar-section">
                        <div class="section-header" onclick="toggleOnlineUsers()">
                            <span><?= __('online_users') ?></span>
                            <span id="online-users-toggle-icon">▾</span>
                        </div>
                        <div id="online-users-list" class="sidebar-list"></div>
                    </div>
                </aside>

                <?php include 'includes/modals.php'; ?>
            </section>
            <section id="dm-pane" class="content-pane" style="display:none;height:100%; flex-direction:column;">
                <!-- Friend Hub (Default View) -->
                <div id="dm-hub-view" style="display:flex; flex-direction:column; height:100%;">
                    <div class="chat-header">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="<?= __('menu') ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <h3><?= __('friend_hub') ?></h3>
                        <div style="margin-left:auto; display:flex; gap:10px;">
                            <button class="btn-primary" onclick="showAddFriendModal()"><?= __('add_friend') ?></button>
                            <button class="btn-primary" onclick="showPendingRequestsModal()" id="btn-pending-req"><?= __('approve_friend') ?></button>
                            <button class="btn-primary" onclick="showBlockedModal()" style="background-color: #333"><?= __('block_list') ?></button>
                        </div>
                    </div>
                    <div class="scroller" style="flex:1; padding:20px; overflow-y:auto;">
                        <h4 style="margin-bottom:10px; color:var(--text-secondary);"><?= __('friend_list') ?></h4>
                        <div id="hub-friend-list" class="thread-list"></div>
                    </div>
                </div>

                <!-- DM Chat View (Hidden by default) -->
                <div id="dm-chat-view" style="display:none; flex-direction:column; height:100%;">
                    <header class="chat-header">
                        <button class="icon-btn" onclick="backToHub()" title="<?= __('back') ?>" style="margin-right:10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="thread-info" id="current-dm-partner-info">
                            <span class="thread-icon">@</span>
                            <h3 class="thread-name" id="current-dm-partner-name"><?= __('select_user') ?></h3>
                        </div>
                        <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                            <button class="icon-btn" onclick="startMeeting()" title="<?= __('video_meeting') ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="showAttachmentGallery()" title="<?= __('attachment_list') ?>">
                                <img src="assets/img/files.svg" alt="<?= __('attachment_gallery') ?>" style="width:16px; height:16px; filter: grayscale(1) invert(1);">
                            </button>
                            <button class="icon-btn" onclick="blockCurrentPartner()" title="<?= __('block') ?>"
                                style="color:#ef4444;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <div id="dm-message-container" class="messages-container">
                        <div class="empty-state">
                            <p><?= __('no_thread_selected') ?></p>
                        </div>
                    </div>

                    <div id="dm-upload-preview" class="upload-preview-bar"
                        style="display:none; padding:10px; border-bottom:1px solid var(--border-color);">
                        <div id="dm-preview-content"></div>
                        <button class="close-btn" onclick="cancelDmUpload()">&times;</button>
                    </div>

                    <div id="pwa-install-banner-dm" class="pwa-install-banner-integrated" style="display:none;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:1.2rem;">📱</span>
                            <span style="font-weight:600;"><?= __('install_sycs') ?></span>
                        </div>
                        <button class="btn-add" onclick="installPWA()"><?= __('install') ?></button>
                        <button class="btn-close" onclick="dismissInstallBanner()">✕</button>
                    </div>

                    <div id="dm-typing-indicator" class="typing-indicator-bar" style="font-size: 0.75rem; color: var(--text-secondary); margin: 0 16px; min-height: 18px;"></div>

                    <div class="chat-input-area" id="dm-chat-area">
                        <div class="input-wrapper">
                            <button class="upload-btn-plus" title="<?= __('upload') ?>" onclick="openMediaUploadModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <textarea id="dm-msg-input" class="chat-input" placeholder="<?= __('dm_placeholder') ?>"
                                rows="1" onkeydown="handleDmInputKey(event)" oninput="handleTyping()"></textarea>
                            <button class="send-btn-modern" onclick="sendDm()">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13" />
                                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modals -->
            <dialog id="add-friend-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3><?= __('add_friend') ?></h3>
                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <input type="text" id="user-search-input" class="chat-input" placeholder="<?= __('search_user_placeholder') ?>">
                        <button class="btn-primary" onclick="searchUsers()"><?= __('search') ?></button>
                    </div>
                    <div id="user-search-results" style="max-height:300px; overflow-y:auto;"></div>
                    <button class="btn-secondary" onclick="document.getElementById('add-friend-modal').close()" style="width:100%; margin-top:10px;"><?= __('close') ?></button>
                </div>
            </dialog>

            <dialog id="gallery-modal" class="modal"
                style="border:none; border-radius:12px; padding:0; background:var(--bg-color); color:var(--text-primary); width:90%; max-width:800px; max-height:80vh;">
                <div style="display:flex; flex-direction:column; height:100%;">
                    <div style="padding:16px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0;"><?= __('attachment_gallery') ?></h3>
                        <button class="close-btn" onclick="document.getElementById('gallery-modal').close()" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer;">✕</button>
                    </div>
                    <div id="gallery-content" style="flex:1; padding:20px; overflow-y:auto; display:grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:16px;">
                        <!-- Attachments will be loaded here -->
                    </div>
                </div>
            </dialog>

            <dialog id="pending-requests-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3><?= __('pending_requests') ?></h3>
                    <div id="pending-requests-list-modal" class="thread-list"
                        style="max-height:300px; overflow-y:auto;"></div>
                    <div class="modal-actions" style="margin-top:10px; text-align:right;">
                        <button class="btn-secondary"
                            onclick="document.getElementById('pending-requests-modal').close()"><?= __('close') ?></button>
                    </div>
                </div>
            </dialog>

            <!-- WebRTC Meeting Modal -->
            <dialog id="meeting-modal" class="modal meeting-modal" style="border:none; border-radius:12px; padding:0; background:#000; width:100vw; height:100vh; max-width:100vw; max-height:100vh; margin:0; overflow:hidden;">
                <div class="video-grid-container" id="video-grid">
                    <!-- Local video and remote videos will be injected here -->
                </div>
                <div class="meeting-controls">
                    <button class="control-btn" id="toggle-mic" onclick="meetingManager.toggleMic()" title="<?= __('toggle_mic') ?>">
                        <img id="mic-icon" src="assets/img/mic.svg" alt="">
                    </button>
                    <button class="control-btn" id="toggle-video" onclick="meetingManager.toggleVideo()" title="<?= __('toggle_video') ?>">
                        <img id="video-icon" src="assets/img/camera_on.svg" alt="">
                    </button>
                    <button class="control-btn" id="toggle-screen" onclick="meetingManager.toggleScreenShare()" title="<?= __('toggle_screen') ?>">
                        <img id="screen-icon" src="assets/img/screen_share.svg" alt="">
                    </button>
                    <button class="control-btn" id="hangup-btn" onclick="meetingManager.leave()" title="<?= __('leave_meeting') ?>">
                        <img id="hangup-icon" src="assets/img/hangup.svg" alt="" color="white">
                    </button>
                </div>
            </dialog>

            <dialog id="blocked-users-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3><?= __('blocked_users') ?></h3>
                    <div id="blocked-users-list" class="thread-list" style="max-height:300px; overflow-y:auto;"></div>
                    <div class="modal-actions" style="margin-top:10px; text-align:right;">
                        <button class="btn-secondary"
                            onclick="document.getElementById('blocked-users-modal').close()"><?= __('close') ?></button>
                    </div>
                </div>
            </dialog>

            <!-- Pinned Messages Modal -->
            <dialog id="pinned-messages-modal" class="modal"
                style="border:none; border-radius:12px; padding:0; background:var(--bg-color); color:var(--text-primary); width:90%; max-width:720px; max-height:80vh;">
                <div style="display:flex; flex-direction:column; height:100%;">
                    <div style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; background:var(--bg-secondary);">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <img src="assets/img/pin.svg" alt="" width="16" height="16">
                            <h3 style="margin:0; font-size:1rem;"><?= __('pinned_messages') ?></h3>
                        </div>
                        <button class="close-btn" onclick="document.getElementById('pinned-messages-modal').close()" style="background:none; border:none; color:var(--text-primary); font-size:1.2rem; cursor:pointer;">✕</button>
                    </div>
                    <div id="pinned-messages-list" style="flex:1; overflow-y:auto; padding:16px;">
                        <div style="text-align:center; color:var(--text-secondary); padding:40px 0;"><?= __('loading') ?></div>
                    </div>
                </div>
            </dialog>

            <!-- Keyboard Shortcuts Help Modal -->
            <dialog id="keyboard-shortcuts-modal" class="modal"
                style="border:none; border-radius:12px; padding:24px; background:var(--accent-hover); color:var(--text-primary); width:90%; max-width:480px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0; display:flex; align-items:center; gap:8px;"><span>⌨️</span> <?= __('keyboard_shortcuts') ?></h3>
                    <button onclick="document.getElementById('keyboard-shortcuts-modal').close()" style="background:none; border:none; color:var(--text-primary); font-size:1.2rem; cursor:pointer;">✕</button>
                </div>
                <div style="display:grid; gap:10px;">
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Esc</kbd>
                        <span style="font-size:0.9rem;"><?= __('shortcut_esc_desc') ?></span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">/</kbd>
                        <span style="font-size:0.9rem;"><?= __('search') ?></span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Alt + P</kbd>
                        <span style="font-size:0.9rem;"><?= __('pinned_messages') ?></span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Alt + Shift + ?</kbd>
                        <span style="font-size:0.9rem;"><?= __('keyboard_shortcuts') ?></span>
                    </div>
                    <div style="display:flex; gap:12px; align-items:center; padding:8px; background:var(--bg-color); border-radius:6px;">
                        <kbd style="background:var(--input-bg); padding:3px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem; border:1px solid var(--border-color); min-width:60px; text-align:center;">Enter</kbd>
                        <span style="font-size:0.9rem;"><?= __('shortcut_enter_desc') ?></span>
                    </div>
                </div>
            </dialog>

            <dialog id="thread-settings-modal" class="modal"
                style="border:none; border-radius:8px; padding:1rem; color:var(--text-primary);">
                <div class="modal-content" style="min-width:400px;">
                    <h3><?= __('thread_settings') ?></h3>
                    <div class="form-group" style="margin-top:1rem;">
                        <label><?= __('thread_name') ?></label>
                        <input type="text" id="settings-thread-name" class="chat-input" style="width:100%;" placeholder="<?= __('thread_name') ?>">
                    </div>
                    <div class="form-group" style="margin-top:1rem;">
                        <label><?= __('category') ?></label>
                        <input type="text" id="settings-thread-category" class="chat-input" style="width:100%;" placeholder="<?= __('category_placeholder') ?>">
                    </div>
                    <div class="form-group" style="margin-top:1rem;">
                        <label><?= __('discord_webhook') ?></label>
                        <input type="text" id="settings-thread-webhook" class="chat-input" style="width:100%;" placeholder="https://discord.com/api/webhooks/...">
                        <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:5px;"><?= __('discord_webhook_desc') ?></p>
                    </div>
                    <div class="modal-actions" style="margin-top:20px; text-align:right;">
                        <button class="btn-secondary" onclick="document.getElementById('thread-settings-modal').close()"><?= __('cancel') ?></button>
                        <button class="btn-primary" onclick="saveThreadSettings()"><?= __('save') ?></button>
                    </div>
                </div>
            </dialog>
            <!-- Media Upload Modal -->
            <dialog id="media-upload-modal" class="modal media-upload-modal">
                <div class="modal-content" style="min-width: 450px; max-width: 600px;">
                    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="margin:0;"><?= __('send_file') ?></h3>
                        <button class="close-btn" onclick="closeMediaUploadModal()">
                            <p style="font-size: 20px; color: #000000; font-weight: bold; margin:0; padding:0; cursor:pointer; background-color: transparent; border: none; outline: none;">✕</p>
                        </button>
                    </div>

                    <div id="media-upload-dropzone" class="upload-dropzone" onclick="document.getElementById('modal-file-input').click()">
                        <div id="media-upload-preview-container" class="upload-preview-container">
                            <div class="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 15px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p style="margin:0; color:var(--text-secondary);"><?= __('click_or_drag_to_select') ?></p>
                            </div>
                        </div>
                        <input type="file" id="modal-file-input" hidden onchange="handleMediaUploadFiles(this.files)">
                    </div>

                    <div class="modal-form-group" style="margin-top:20px;">
                        <label class="modal-label"><?= __('message_optional') ?></label>
                        <textarea id="modal-content-input" class="modal-textarea" placeholder="<?= __('bio_placeholder') ?>" rows="2" style="background:var(--input-bg); border:1px solid var(--border-color); color:white; border-radius:8px; padding:12px; width:100%; resize:none;"></textarea>
                    </div>

                    <div class="modal-actions" style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
                        <button class="btn-secondary" onclick="closeMediaUploadModal()" style="padding:10px 30px;"><?= __('cancel') ?></button>
                    </div>
                </div>
            </dialog>
            <section id="favorites-pane" class="content-pane" style="display:none;">
                <aside class="thread-browser active"
                    style="margin-left:0; border-right:1px solid var(--border-color); display:block; position:relative;">
                    <div class="panel-header" style="justify-content: flex-start;">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="<?= __('menu') ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <div style="display:flex; align-items:center; margin-left:10px;"><?= __('fav_threads') ?></div>
                    </div>
                    <div id="fav-thread-list" class="thread-list"></div>
                </aside>
            </section>

        </main>
    </div>

    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="js/webrtc.js"></script>
    <script src="js/locate.js"></script>


    <!-- PWA Installation Logic moved to integrated locations -->

    <!-- Offline Indicator -->
    <div id="offline-indicator" style="display:none; position:fixed; top:0; left:0; right:0; background:#ef4444; color:white; text-align:center; padding:6px; font-size:0.8rem; font-family:'Inter',sans-serif; z-index:10001; animation: slideDown 0.3s ease-out;">
        <?= __('offline_msg') ?>
    </div>
    <script src="js/index.js" type="module"></script>
    <script src="js/widgets.js"></script>
</body>

</html>

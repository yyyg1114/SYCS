<?php

/**
 * SYCS - Direct Message Page
 * v1.2.38
 */

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
$initialThreadId = 0;
$currentThreadCreatorId = 0;

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($isLoggedIn) {
    $stmt = $mysqli->prepare("SELECT status, avatar_url, banner_color, banner_url, profile_layout, social_links, theme_preference, notification_keywords FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $currentUserStatus = $row['status'] ?: 'online';
        $currentUserAvatar = $row['avatar_url'];
        $currentUserBanner = $row['banner_color'] ?: '#6366f1';
        $currentUserBannerUrl = $row['banner_url'];
        $currentUserProfileLayout = $row['profile_layout'] ?: 'classic';
        $currentUserSocialLinks = json_decode($row['social_links'] ?: '{}', true);
        $currentUserThemePref = json_decode($row['theme_preference'] ?: '{}', true);
        $currentUserKeywords = $row['notification_keywords'] ?: '';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::getInstance()->getCurrentLang() ?>">

<head>
    <meta charset="UTF-8">
    <title>SYCS - DM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

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
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    <?php include __DIR__ . '/includes/config.php'; ?>
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <?php
        $currentPage = 'dm';
        include 'includes/sidebar.php';
        ?>

        <main class="main-content">
            <section id="dm-pane" class="content-pane active" style="display:flex; height:100%; flex-direction:column;">
                <!-- Friend Hub (Default View) -->
                <div id="dm-hub-view" style="display:flex; flex-direction:column; height:100%;">
                    <div class="chat-header">
                        <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()" title="<?= ('menu') ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                        <h3><?= ('friend_hub') ?></h3>
                        <div style="margin-left:auto; display:flex; gap:10px;">
                            <button class="btn-primary" onclick="showAddFriendModal()"><?= ('add_friend') ?></button>
                            <button class="btn-primary" onclick="showPendingRequestsModal()" id="btn-pending-req"><?= ('approve_friend') ?></button>
                            <button class="btn-primary" onclick="showBlockedModal()" style="background-color: #333"><?= ('block_list') ?></button>
                        </div>
                    </div>
                    <div class="scroller" style="flex:1; padding:20px; overflow-y:auto;">
                        <h4 style="margin-bottom:10px; color:var(--text-secondary);"><?= ('friend_list') ?></h4>
                        <div id="hub-friend-list" class="thread-list"></div>
                    </div>
                </div>

                <!-- DM Chat View (Hidden by default) -->
                <div id="dm-chat-view" style="display:none; flex-direction:column; height:100%;">
                    <header class="chat-header">
                        <button class="icon-btn" onclick="backToHub()" title="<?= ('back') ?>" style="margin-right:10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="thread-info" id="current-dm-partner-info">
                            <span class="thread-icon">@</span>
                            <h3 class="thread-name" id="current-dm-partner-name"><?= ('select_user') ?></h3>
                        </div>
                        <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                            <button class="icon-btn" onclick="startMeeting()" title="<?= ('video_meeting') ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="showAttachmentGallery()" title="<?= ('attachment_list') ?>">
                                <img src="assets/img/files.svg" alt="<?= ('attachment_gallery') ?>" style="width:16px; height:16px; filter: grayscale(1) invert(1);">
                            </button>
                            <button class="icon-btn" onclick="blockCurrentPartner()" title="<?= ('block') ?>"
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
                            <p><?= ('no_thread_selected') ?></p>
                        </div>
                    </div>

                    <div id="dm-typing-indicator" class="typing-indicator-bar" style="font-size: 0.75rem; color: var(--text-secondary); margin: 0 16px; min-height: 18px;"></div>

                    <div class="chat-input-area" id="dm-chat-area">
                        <div class="input-wrapper">
                            <button class="upload-btn-plus" title="<?= ('upload') ?>" onclick="openMediaUploadModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <textarea id="dm-msg-input" class="chat-input" placeholder="<?= ('dm_placeholder') ?>"
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
        </main>
    </div>

    <?php include 'includes/modals.php'; ?>

    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="js/webrtc.js"></script>
    <script src="js/locate.js"></script>
    <script src="js/index.js" type="module"></script>
    <script src="js/widgets.js"></script>
    <script>
        // Ensure DM tab is active in the logic if index.js is shared
        window.addEventListener('DOMContentLoaded', () => {
            const checkSwitchTab = setInterval(() => {
                if (typeof window.switchTab === 'function') {
                    window.switchTab('dm');
                    clearInterval(checkSwitchTab);
                }
            }, 100);
            // Safety timeout
            setTimeout(() => clearInterval(checkSwitchTab), 5000);
        });
    </script>
</body>

</html>

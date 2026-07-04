<?php

/**
 * SYCS - Favorites Page
 * v1.0.0
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

// 4. Auth Status Check
$isLoggedIn = isset($_SESSION['user']);
if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];
$currentUserStatus = 'online';
$currentUserAvatar = '';
$currentUserThemePref = [];

if ($isLoggedIn) {
    $stmt = $mysqli->prepare("SELECT status, avatar_url, theme_preference FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $currentUserStatus = $row['status'] ?: 'online';
        $currentUserAvatar = $row['avatar_url'];
        $currentUserThemePref = json_decode($row['theme_preference'] ?: '{}', true);
    }
    $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= I18n::getInstance()->getCurrentLang() ?>">

<head>
    <meta charset="UTF-8">
    <title>SYCS - <?= __('favorites') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="icon" href="assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem("sycs_theme");
                const supportDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches;
                const theme = savedTheme || (supportDarkMode ? "dark" : "light");

                if (theme === "light") {
                    document.documentElement.classList.add("light-theme");
                    document.documentElement.classList.remove("dark-theme");
                    document.documentElement.classList.remove("night-theme");
                } else if (theme === "night") {
                    document.documentElement.classList.add("night-theme");
                    document.documentElement.classList.remove("light-theme");
                    document.documentElement.classList.remove("dark-theme");
                } else {
                    document.documentElement.classList.add("dark-theme");
                    document.documentElement.classList.remove("light-theme");
                    document.documentElement.classList.remove("night-theme");
                }
            } catch (e) {
                console.error("Theme initialization failed", e);
            }
        })();
    </script>

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="stylesheet" href="css/indicators.css">
    <link rel="stylesheet" href="css/widgets.css">

    <?php include __DIR__ . '/includes/config.php'; ?>
</head>

<body>
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    <div class="app-container">
        <?php
        $currentPage = 'favorites';
        include 'includes/sidebar.php';
        ?>

        <main class="main-content" style="flex-direction: column;">
            <?php
            $headerTitle = __('favorites');
            $headerIcon = '★';
            include 'includes/app_header.php';
            ?>

            <div class="scroller" style="flex:1; padding: 24px; overflow-y: auto;">
                <div style="max-width: 1000px; margin: 0;">
                    <div style="margin-bottom: 32px;">
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; margin-bottom: 8px;"><?= __('fav_threads') ?></h2>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= __('fav_threads_desc') ?? 'お気に入りに登録したスレッドのリストです。' ?></p>
                    </div>

                    <div id="fav-thread-list" class="thread-list" style="background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden;">
                        <!-- Favorites will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/modals.php'; ?>

    <script src="js/widgets.js"></script>
    <script src="js/index.js" type="module"></script>
    <script type="module">
        import {
            loadFavorites
        } from './js/modules/favorites.js';
        window.addEventListener('load', () => {
            loadFavorites();
        });
    </script>
</body>

</html>

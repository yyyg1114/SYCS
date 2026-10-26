<?php

/**
 * meetings.php - Meeting ID & Password based Meeting System
 * Separate from index.php for clean meeting management
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/session_config.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';

// 1. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// --- Database Schema Support ---
// Ensure columns exist (Safe Migration logic)
try {
    $mysqli->query("ALTER TABLE meeting_rooms ADD COLUMN IF NOT EXISTS meeting_id VARCHAR(20) AFTER dm_user_2");
    $mysqli->query("ALTER TABLE meeting_rooms ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) AFTER meeting_id");
} catch (\Throwable $e) {
    // Ignore migration error if columns exist
}

// URLパラメータ（招待リンク等）の取得
$initialMid = preg_replace('/[\s\-_]/', '', trim((string)($_GET['mid'] ?? '')));
$initialPwd = trim((string)($_GET['pwd'] ?? ''));
$initialTab = isset($_GET['create']) ? 'create' : 'join';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <script>
        window.SYCS_CONFIG = {
            currentUserId: <?= json_encode($_SESSION['user_id'] ?? null) ?>,
            currentUserName: <?= json_encode($_SESSION['username'] ?? 'User') ?>,
            csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? null) ?>
        };
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting SYCS - ビデオ会議</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0a0f1d;
            --bg-card: rgba(17, 24, 39, 0.72);
            --bg-input: rgba(15, 23, 42, 0.65);
            /*--accent: #6366f1;
        --accent-hover: #4f46e5;
        --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);*/
            --btn-join-bg: #0e9952;
            --btn-join-hover-bg: #00ff7bb7;
            --btn-create-bg: #6366f1;
            --btn-create-hover-bg: #4f46e5;
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-active: rgba(99, 102, 241, 0.5);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle at 50% 10%, #1e1b4b 0%, var(--bg-base) 65%);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 520px;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 28px;
            padding: 2.25rem;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.65), 0 0 40px rgba(99, 102, 241, 0.12);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--btn-create-bg);
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .header-icon svg {
            width: 28px;
            height: 28px;
            fill: white;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Tabs Navigation */
        .tab-nav {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            background: rgba(0, 0, 0, 0.28);
            padding: 5px;
            border-radius: 14px;
            border: 1px solid var(--border-glass);
            margin-bottom: 1.75rem;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.65rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.04);
        }

        .tab-btn.active {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        /* Input Controls */
        .input-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            border: 1px solid var(--border-glass);
            background: var(--bg-input);
            color: white;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--border-active);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18);
            background: rgba(15, 23, 42, 0.85);
        }

        .meeting-id-input {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            letter-spacing: 0.12em;
            text-align: center;
            font-weight: 600;
        }

        .toggle-pwd-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 6px;
            cursor: pointer;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin: 0;
            width: auto;
        }

        .toggle-pwd-btn:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.08);
            opacity: 1;
            transform: translateY(-50%);
        }

        /* Buttons */
        .primary-btn-join {
            width: 100%;
            padding: 0.85rem 1.5rem;
            border-radius: 14px;
            border: none;
            background: var(--btn-join-bg);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            /*box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);*/
        }

        .primary-btn-join:hover {
            transform: translateY(-2px);
            /*box-shadow: 0 14px 25px -5px rgba(99, 102, 241, 0.55);*/
            opacity: 0.98;
            background: var(--btn-join-hover-bg);
        }

        .primary-btn-join:active {
            transform: translateY(0);
        }

        .primary-btn-create {
            width: 100%;
            padding: 0.85rem 1.5rem;
            border-radius: 14px;
            border: none;
            background: var(--btn-create-bg);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .primary-btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px -5px rgba(99, 102, 241, 0.55);
            opacity: 0.98;
            background: var(--btn-create-hover-bg);
        }

        .primary-btn-create:active {
            transform: translateY(0);
        }

        .secondary-btn {
            background: rgba(255, 255, 255, 0.08);
            box-shadow: none;
        }

        .secondary-btn:hover {
            background: rgba(255, 255, 255, 0.14);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        /* Options for Meeting Creation */
        .passcode-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            background: rgba(0, 0, 0, 0.2);
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid var(--border-glass);
        }

        .passcode-opt-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0;
            width: auto;
        }

        .passcode-opt-btn.active {
            background: rgba(99, 102, 241, 0.25);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.4);
            font-weight: 600;
        }

        /* Result Area */
        #result-area {
            margin-top: 1.75rem;
            padding: 1.5rem;
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 18px;
            display: none;
            animation: slideDown 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 1rem;
            color: #c7d2fe;
            margin-bottom: 1.25rem;
        }

        .info-card-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.35);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .info-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            color: #e0e7ff;
            letter-spacing: 0.05em;
        }

        .copy-chip-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            width: auto;
        }

        .copy-chip-btn:hover {
            background: rgba(255, 255, 255, 0.16);
            color: white;
            transform: translateY(-1px);
        }

        .copy-chip-btn.copied {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.4);
        }

        .action-row {
            display: flex;
            gap: 8px;
            margin-top: 1rem;
        }

        .action-row button {
            margin-top: 0;
        }

        /* Toast Notification */
        #toast {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
            z-index: 999999;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        #toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Video Modal Overrides */
        #meeting-modal {
            background: #000;
            border: none;
            width: 100vw;
            height: 100vh;
            max-width: 100vw;
            max-height: 100vh;
            margin: 0;
            padding: 0;
            color: white;
        }

        .video-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1rem;
            height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .video-wrapper {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-label {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .meeting-controls {
            position: fixed !important;
            bottom: 30px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 16px !important;
            background: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            padding: 12px 24px !important;
            border-radius: 9999px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6) !important;
            z-index: 99999 !important;
            width: auto !important;
            height: auto !important;
            max-width: 90vw !important;
        }

        .meeting-controls .control-btn {
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            min-height: 48px !important;
            max-width: 48px !important;
            max-height: 48px !important;
            border-radius: 50% !important;
            border: none !important;
            background: rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }

        .meeting-controls .control-btn:hover {
            background: rgba(255, 255, 255, 0.25) !important;
            transform: translateY(-2px) scale(1.05) !important;
        }

        .meeting-controls .control-btn.active {
            background: #6366f1 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.muted {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.hangup-btn,
        .meeting-controls .control-btn.hangup,
        .meeting-controls .control-btn#hangup-btn {
            background: #ef4444 !important;
            color: #ffffff !important;
        }

        .meeting-controls .control-btn.hangup-btn:hover,
        .meeting-controls .control-btn.hangup:hover,
        .meeting-controls .control-btn#hangup-btn:hover {
            background: #dc2626 !important;
        }

        .meeting-controls .control-btn img,
        .meeting-controls .control-btn svg {
            width: 22px !important;
            height: 22px !important;
            max-width: 22px !important;
            max-height: 22px !important;
            object-fit: contain !important;
            flex-shrink: 0 !important;
            filter: brightness(0) invert(1) !important;
        }

        /* In-meeting Info Box */
        #meeting-info-box {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 1.25rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            z-index: 1000;
            display: none;
            text-align: left;
            min-width: 260px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.2s ease-out;
        }

        #meeting-info-box h4 {
            margin: 0 0 0.75rem 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .in-meeting-info-item {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            color: #818cf8;
            font-weight: 600;
        }
    </style>
    <script type="module" src="../backend/js/webrtc/media-manager.js"></script>
    <script type="module" src="../backend/js/webrtc/signaling-client.js"></script>
    <script type="module" src="../backend/js/webrtc/peer-connection-manager.js"></script>
    <script type="module" src="../backend/js/webrtc/meeting-ui.js"></script>
    <script type="module" src="../backend/js/webrtc/meeting-manager.js"></script>
</head>

<body>

    <div class="container" id="join-card">
        <div class="card">
            <div class="header">
                <div class="header-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 10.5V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3.5l4 4v-11l-4 4z" />
                    </svg>
                </div>
                <h1>SYCS Meetings</h1>
                <!--<p class="subtitle">シンプルで安全な WebRTC ビデオ会議</p>-->
            </div>

            <!-- Tab Switcher -->
            <div class="tab-nav">
                <button type="button" class="tab-btn <?= $initialTab === 'join' ? 'active' : '' ?>" id="tab-btn-join" onclick="switchTab('join')">
                    ミーティングに参加
                </button>
                <button type="button" class="tab-btn <?= $initialTab === 'create' ? 'active' : '' ?>" id="tab-btn-create" onclick="switchTab('create')">
                    新規作成
                </button>
            </div>

            <!-- TAB 1: 参加フォーム -->
            <div id="tab-content-join" style="display: <?= $initialTab === 'join' ? 'block' : 'none' ?>;">
                <div class="input-group">
                    <label for="m-id">
                        <span>ミーティングID</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">9桁の数字</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="m-id" class="meeting-id-input" placeholder="123 456 789" maxlength="11" autocomplete="off" value="<?= htmlspecialchars($initialMid, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label for="m-pass">
                        <span>パスワード</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">(設定されている場合)</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="m-pass" placeholder="パスワードを入力" value="<?= htmlspecialchars($initialPwd, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="toggle-pwd-btn" onclick="togglePasswordVisibility('m-pass', this)" title="パスワードを表示">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="button" class="primary-btn-join" id="join-btn" onclick="joinMeeting()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    ミーティングに参加
                </button>
            </div>

            <!-- TAB 2: 新規作成フォーム -->
            <div id="tab-content-create" style="display: <?= $initialTab === 'create' ? 'block' : 'none' ?>;">
                <div class="input-group">
                    <label>パスワード保護</label>
                    <div class="passcode-options">
                        <button type="button" class="passcode-opt-btn active" id="opt-auto" onclick="setPasscodeOption('auto')">自動生成 (推奨)</button>
                        <button type="button" class="passcode-opt-btn" id="opt-custom" onclick="setPasscodeOption('custom')">カスタム指定</button>
                        <button type="button" class="passcode-opt-btn" id="opt-none" onclick="setPasscodeOption('none')">パスワードなし</button>
                    </div>
                </div>

                <div class="input-group" id="custom-passcode-group" style="display: none;">
                    <label for="custom-passcode">カスタムパスワード</label>
                    <div class="input-wrapper">
                        <input type="text" id="custom-passcode" placeholder="例: meeting2026">
                    </div>
                </div>

                <button type="button" class="primary-btn-create" id="create-btn" onclick="createMeeting()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    新しいミーティングを作成
                </button>

                <!-- 作成結果カード -->
                <div id="result-area">
                    <div class="result-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        ミーティングが準備できました！
                    </div>

                    <div class="info-card-row">
                        <div>
                            <div class="info-label">ミーティングID</div>
                            <div class="info-value" id="res-id">--- --- ---</div>
                        </div>
                        <button type="button" class="copy-chip-btn" id="copy-id-btn" onclick="copyMeetingId()">
                            コピー
                        </button>
                    </div>

                    <div class="info-card-row" id="res-pass-row">
                        <div>
                            <div class="info-label">パスワード</div>
                            <div class="info-value" id="res-pass">------</div>
                        </div>
                        <button type="button" class="copy-chip-btn" id="copy-pass-btn" onclick="copyMeetingPass()">
                            コピー
                        </button>
                    </div>

                    <div class="action-row">
                        <button type="button" class="copy-chip-btn" style="flex: 1; padding: 0.65rem;" onclick="copyFullInvite()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            招待情報をコピー
                        </button>
                        <button type="button" class="copy-chip-btn" style="flex: 1; padding: 0.65rem;" onclick="copyInviteUrl()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                            URLをコピー
                        </button>
                    </div>

                    <button type="button" class="primary-btn-join" onclick="joinCreatedMeeting()" style="margin-top: 1rem;">
                        作成した会議に入る
                    </button>
                </div>
            </div>

            <!-- Footer Navigation -->
            <div style="margin-top: 2rem; border-top: 1px solid var(--border-glass); padding-top: 1.25rem;">
                <a href="index.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: color 0.2s;">
                    <span>← ホーム（チャット）に戻る</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span id="toast-text">コピーしました</span>
    </div>

    <!-- WebRTC Meeting Room Modal -->
    <dialog id="meeting-modal" class="modal meeting-modal" style="border:none; border-radius:0; padding:0; background:#000; width:100vw; height:100vh; max-width:100vw; max-height:100vh; margin:0; overflow:hidden;">
        <div id="meeting-info-box">
            <h4>
                <span>ミーティング情報</span>
                <button type="button" onclick="toggleMeetingInfo()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:0; width:auto; margin:0;">✕</button>
            </h4>
            <div style="margin-bottom: 0.5rem;">
                <div style="font-size:0.75rem; color:var(--text-muted);">ミーティングID</div>
                <div id="info-id" class="in-meeting-info-item">--- --- ---</div>
            </div>
            <div id="info-pass-row" style="margin-bottom: 0.75rem;">
                <div style="font-size:0.75rem; color:var(--text-muted);">パスワード</div>
                <div id="info-pass" class="in-meeting-info-item">------</div>
            </div>
            <button type="button" class="copy-chip-btn" style="width:100%; justify-content:center;" onclick="copyFullInvite()">
                招待情報をコピー
            </button>
        </div>

        <div class="video-grid-container" id="video-grid">
            <!-- Local video and remote videos will be injected here -->
        </div>

        <div class="meeting-controls">
            <button class="control-btn" id="toggle-info" onclick="toggleMeetingInfo()" title="ミーティング詳細">
                <img id="info-icon" src="assets/img/info.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-mic" onclick="meetingManager.toggleMic()" title="マイク オン/オフ">
                <img id="mic-icon" src="assets/img/mic.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-video" onclick="meetingManager.toggleVideo()" title="カメラ オン/オフ">
                <img id="video-icon" src="assets/img/camera_on.svg" alt="">
            </button>
            <button class="control-btn" id="toggle-screen" onclick="meetingManager.toggleScreenShare()" title="画面共有">
                <img id="screen-icon" src="assets/img/screen_share.svg" alt="">
            </button>
            <button class="control-btn hangup-btn" id="hangup-btn" style="background-color: white;" onclick="meetingManager.leave()" title="退席">
                <img id="hangup-icon" src="assets/img/hangup.svg" alt="" color="white">
            </button>
        </div>
    </dialog>

    <script>
        const currentUserId = <?= json_encode($userId) ?>;
        const currentUsername = <?= json_encode($username) ?>;

        let currentPasscodeOption = 'auto';
        let lastCreatedMeeting = null;

        // API Client
        async function api(action, method = 'GET', body = null) {
            const url = `index.php?api=${action}`;
            const options = {
                method
            };
            const csrfToken = window.SYCS_CONFIG?.csrfToken || '<?= $_SESSION['csrf_token'] ?? '' ?>';

            if (body instanceof FormData) {
                body.append('csrf_token', csrfToken);
                options.body = body;
            } else if (method === 'POST') {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                if (body && typeof body === 'object') {
                    for (const key in body) {
                        formData.append(key, body[key]);
                    }
                }
                options.body = formData;
            }

            const res = await fetch(url, options);
            return res.json();
        }

        // Tab Switching
        function switchTab(tab) {
            const isJoin = tab === 'join';
            document.getElementById('tab-btn-join').classList.toggle('active', isJoin);
            document.getElementById('tab-btn-create').classList.toggle('active', !isJoin);
            document.getElementById('tab-content-join').style.display = isJoin ? 'block' : 'none';
            document.getElementById('tab-content-create').style.display = !isJoin ? 'block' : 'none';
        }

        // Passcode Option Selector
        function setPasscodeOption(opt) {
            currentPasscodeOption = opt;
            document.getElementById('opt-auto').classList.toggle('active', opt === 'auto');
            document.getElementById('opt-custom').classList.toggle('active', opt === 'custom');
            document.getElementById('opt-none').classList.toggle('active', opt === 'none');

            document.getElementById('custom-passcode-group').style.display = (opt === 'custom') ? 'block' : 'none';
        }

        // Format Meeting ID input: 123 456 789 (3-3-3 digits)
        const midInput = document.getElementById('m-id');

        function formatMeetingIdString(str) {
            const digits = (str || '').replace(/\D/g, '').slice(0, 9);
            if (digits.length > 6) {
                return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`;
            } else if (digits.length > 3) {
                return `${digits.slice(0, 3)} ${digits.slice(3)}`;
            }
            return digits;
        }

        midInput.addEventListener('input', (e) => {
            const formatted = formatMeetingIdString(e.target.value);
            e.target.value = formatted;
        });

        // Initialize formatted initial value if present
        if (midInput.value) {
            midInput.value = formatMeetingIdString(midInput.value);
        }

        // Password visibility toggle
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            btn.innerHTML = isPassword ?
                `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>` :
                `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>`;
        }

        // Toast display helper
        let toastTimeout = null;

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-text').innerText = msg;
            toast.classList.add('show');
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }

        // Create Meeting
        async function createMeeting() {
            const createBtn = document.getElementById('create-btn');
            createBtn.disabled = true;
            createBtn.innerText = '作成中...';

            try {
                const body = {
                    thread_id: '0',
                    group_thread_id: '0',
                    dm_partner_id: '0',
                    create_meeting_id: '1',
                    passcode_option: currentPasscodeOption
                };

                if (currentPasscodeOption === 'custom') {
                    body.passcode = document.getElementById('custom-passcode').value;
                }

                const res = await api('join_meeting', 'POST', body);
                if (res.success) {
                    lastCreatedMeeting = res;

                    const displayId = res.formatted_meeting_id || res.meeting_id || res.room_name;
                    const displayPass = res.passcode || '(なし)';

                    document.getElementById('res-id').innerText = displayId;
                    document.getElementById('res-pass').innerText = displayPass;
                    document.getElementById('res-pass-row').style.display = res.has_password ? 'flex' : 'none';

                    document.getElementById('result-area').style.display = 'block';

                    // Update in-meeting info box
                    document.getElementById('info-id').innerText = displayId;
                    document.getElementById('info-pass').innerText = displayPass;
                    document.getElementById('info-pass-row').style.display = res.has_password ? 'block' : 'none';

                    showToast('ミーティングを作成しました');
                } else {
                    alert("作成エラー: " + (res.error || "作成できませんでした"));
                }
            } catch (err) {
                console.error(err);
                alert("通信エラーが発生しました");
            } finally {
                createBtn.disabled = false;
                createBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                新しいミーティングを作成
            `;
            }
        }

        // Join Created Meeting immediately
        async function joinCreatedMeeting() {
            if (lastCreatedMeeting) {
                await meetingManager.start({
                    roomId: lastCreatedMeeting.room_id
                });
            }
        }

        // Join Meeting by ID
        async function joinMeeting() {
            const rawId = document.getElementById('m-id').value;
            const mId = rawId.replace(/[\s\-_]/g, '');
            const mPass = document.getElementById('m-pass').value;

            if (!mId) {
                alert("ミーティングIDを入力してください");
                document.getElementById('m-id').focus();
                return;
            }

            const joinBtn = document.getElementById('join-btn');
            joinBtn.disabled = true;
            joinBtn.innerText = '接続中...';

            try {
                const body = new FormData();
                body.append('meeting_id', mId);
                body.append('passcode', mPass);

                const res = await api('join_by_meeting_id', 'POST', body);
                if (res.success) {
                    const formattedId = res.formatted_meeting_id || formatMeetingIdString(mId);
                    document.getElementById('info-id').innerText = formattedId;
                    document.getElementById('info-pass').innerText = mPass ? mPass : '(なし)';
                    document.getElementById('info-pass-row').style.display = mPass ? 'block' : 'none';

                    lastCreatedMeeting = {
                        meeting_id: res.meeting_id || mId,
                        formatted_meeting_id: formattedId,
                        passcode: mPass || null
                    };

                    await meetingManager.start({
                        roomId: res.room_id
                    });
                } else {
                    if (res.require_passcode) {
                        alert("このミーティングにはパスワードが必要です。パスワードを入力してください。");
                        document.getElementById('m-pass').focus();
                    } else {
                        alert(res.error || "参加に失敗しました");
                    }
                }
            } catch (err) {
                console.error(err);
                alert("通信エラーが発生しました");
            } finally {
                joinBtn.disabled = false;
                joinBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                ミーティングに参加
            `;
            }
        }

        // Copy Helpers
        function copyToClipboard(text, successMsg = 'コピーしました') {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(successMsg);
                }).catch(() => fallbackCopy(text, successMsg));
            } else {
                fallbackCopy(text, successMsg);
            }
        }

        function fallbackCopy(text, successMsg) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showToast(successMsg);
            } catch (e) {
                console.error(e);
            }
            document.body.removeChild(textArea);
        }

        function copyMeetingId() {
            if (!lastCreatedMeeting) return;
            const id = lastCreatedMeeting.meeting_id || lastCreatedMeeting.formatted_meeting_id;
            copyToClipboard(id, 'ミーティングIDをコピーしました');
        }

        function copyMeetingPass() {
            if (!lastCreatedMeeting || !lastCreatedMeeting.passcode) return;
            copyToClipboard(lastCreatedMeeting.passcode, 'パスワードをコピーしました');
        }

        function getMeetingInviteUrl() {
            if (!lastCreatedMeeting) return window.location.href;
            const mid = lastCreatedMeeting.meeting_id || '';
            const pwd = lastCreatedMeeting.passcode || '';
            const base = window.location.origin + window.location.pathname;
            let url = `${base}?mid=${encodeURIComponent(mid)}`;
            if (pwd) {
                url += `&pwd=${encodeURIComponent(pwd)}`;
            }
            return url;
        }

        function copyInviteUrl() {
            const url = getMeetingInviteUrl();
            copyToClipboard(url, '参加URLをコピーしました');
        }

        function copyFullInvite() {
            if (!lastCreatedMeeting) return;
            const id = lastCreatedMeeting.formatted_meeting_id || lastCreatedMeeting.meeting_id;
            const pass = lastCreatedMeeting.passcode ? `\nパスコード: ${lastCreatedMeeting.passcode}` : '';
            const url = getMeetingInviteUrl();

            const text = `【SYCS ミーティングの招待】\nミーティングID: ${id}${pass}\n\n参加URL:\n${url}`;
            copyToClipboard(text, '招待テキストをコピーしました');
        }

        // In-Meeting Info Toggle
        function toggleMeetingInfo() {
            const box = document.getElementById('meeting-info-box');
            const btn = document.getElementById('toggle-info');
            const isVisible = box.style.display === 'block';
            box.style.display = isVisible ? 'none' : 'block';
            if (btn) btn.classList.toggle('active', !isVisible);
        }

        // Enter key to submit
        document.getElementById('m-id').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') joinMeeting();
        });
        document.getElementById('m-pass').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') joinMeeting();
        });
    </script>
</body>

</html>

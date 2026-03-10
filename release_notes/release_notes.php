<?php
require_once __DIR__ . '/../backend/I18n.php';

// Initialize I18n
$i18n = I18n::getInstance();
$lang = $i18n->getCurrentLang();

// Determine content file
$contentFile = __DIR__ . '/' . $lang . '.php';
if (!file_exists($contentFile)) {
    $contentFile = __DIR__ . '/ja.php';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __("release_notes_title"); ?></title>
    <meta name="description" content="<?php echo __("release_notes_desc"); ?>">
    <link rel="icon" href="../frontend/assets/img/SYCS_favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg: #0d0f14;
            --bg2: #13161e;
            --bg3: #1a1e28;
            --border: rgba(255, 255, 255, 0.08);
            --text: #e2e8f0;
            --muted: #64748b;
            --accent: #6366f1;
            --accent2: #818cf8;
            --green: #10b981;
            --yellow: #f59e0b;
            --red: #ef4444;
            --blue: #3b82f6;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.7;
        }

        /* ---- Header ---- */
        header {
            background: linear-gradient(135deg, #1a1e30 0%, #0d0f14 100%);
            border-bottom: 1px solid var(--border);
            padding: 60px 24px 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 80% at 50% -20%, rgba(99, 102, 241, 0.18), transparent);
            pointer-events: none;
        }

        .header-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .header-logo img {
            width: 40px;
            height: 40px;
        }

        .header-logo span {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff 30%, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #fff 40%, var(--accent2));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }

        header p {
            color: var(--muted);
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ---- Layout ---- */
        main {
            max-width: 860px;
            margin: 0 auto;
            padding: 48px 24px 96px;
        }

        /* ---- Release Card ---- */
        .release {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 32px;
            transition: border-color 0.2s;
        }

        .release:hover {
            border-color: rgba(99, 102, 241, 0.4);
        }

        .release-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px 28px;
            background: var(--bg3);
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .version-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--accent), #7c3aed);
            color: white;
            padding: 4px 14px;
            border-radius: 9999px;
            white-space: nowrap;
        }

        .release-title {
            font-size: 1.1rem;
            font-weight: 600;
            flex: 1;
        }

        .release-date {
            font-size: 0.8rem;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
        }

        .label-new {
            background: rgba(16, 185, 129, 0.15);
            color: var(--green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .label-fix {
            background: rgba(59, 130, 246, 0.15);
            color: var(--blue);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .label-remove {
            background: rgba(239, 68, 68, 0.15);
            color: var(--red);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .label-improve {
            background: rgba(245, 158, 11, 0.15);
            color: var(--yellow);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .type-label {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .release-body {
            padding: 28px;
        }

        /* ---- Section ---- */
        .section {
            margin-bottom: 28px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .section-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-green {
            background: var(--green);
        }

        .dot-blue {
            background: var(--blue);
        }

        .dot-red {
            background: var(--red);
        }

        .dot-yellow {
            background: var(--yellow);
        }

        ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        li .icon {
            flex-shrink: 0;
            font-size: 1rem;
            margin-top: 2px;
        }

        li .detail {
            color: var(--muted);
            font-size: 0.82rem;
            display: block;
            margin-top: 2px;
        }

        code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82em;
            background: rgba(255, 255, 255, 0.06);
            padding: 1px 6px;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        kbd {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8em;
            background: var(--bg3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-bottom-width: 2px;
            padding: 1px 7px;
            border-radius: 5px;
        }

        /* ---- Footer ---- */
        footer {
            text-align: center;
            padding: 32px 24px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.82rem;
        }

        footer a {
            color: var(--accent2);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .release-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="header-logo">
            <img src="../frontend/assets/img/SYCS_Logo.svg" alt="SYCS Logo">
            <span>SYCS</span>
        </div>
        <h1><?php echo __("release_notes_title"); ?></h1>
        <p><?php echo __("release_notes_desc"); ?></p>
    </header>

    <main>
        <?php include $contentFile; ?>
    </main>

    <footer>
        <p>SYCS &copy; 2026 &nbsp;|&nbsp; <a href="../frontend/index.php"><?php echo __("back_to_app"); ?></a></p>
    </footer>

</body>

</html>

<script>
    window.SYCS_CONFIG = {
        currentThreadId: <?= json_encode($initialThreadId ?? 1) ?>,
        currentThreadCreatorId: <?= json_encode($currentThreadCreatorId ?? 0) ?>,
        isGroupChat: <?= json_encode($isGroupChat ?? false) ?>,
        currentUserId: <?= json_encode($_SESSION['user_id'] ?? null) ?>,
        currentUserName: <?= json_encode($currentUser ?? null) ?>,
        currentUserTheme: <?= json_encode($currentUserThemePref ?? []) ?>,
        userKeywords: <?= json_encode($currentUserKeywords ?? '') ?>,
        translations: <?= json_encode(I18n::getInstance()->getTranslations()) ?>,
        csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? null) ?>,
        vapidPublicKey: <?= json_encode(getenv('VAPID_PUBLIC_KEY') ?: '') ?>,
    };
</script>

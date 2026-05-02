<script>
    window.SYCS_CONFIG = {
        currentThreadId: <?= json_encode($initialThreadId) ?>,
        currentThreadCreatorId: <?= json_encode($currentThreadCreatorId) ?>,
        currentUserId: <?= json_encode($_SESSION['user_id']) ?>,
        currentUserName: <?= json_encode($currentUser) ?>,
        currentUserTheme: <?= json_encode($currentUserThemePref) ?>,
        userKeywords: <?= json_encode($currentUserKeywords) ?>,
        translations: <?= json_encode(I18n::getInstance()->getTranslations()) ?>,
        csrfToken: <?= json_encode($_SESSION['csrf_token']) ?>
    };
</script>

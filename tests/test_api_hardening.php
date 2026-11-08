<?php

require_once __DIR__ . '/../backend/FileRateLimiter.php';
require_once __DIR__ . '/../backend/SecurityUtil.php';
require_once __DIR__ . '/../backend/api/BaseHandler.php';

ob_start();

class TestableBaseHandler extends BaseHandler
{
    public function validateContent($content, bool $allowEmpty = false): bool
    {
        return $this->validateMessageContent($content, $allowEmpty);
    }
}

$passed = 0;
$failed = 0;

function it(string $description, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  [PASS] {$description}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  [FAIL] {$description}\n";
        echo "         " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "--- Running API Hardening Regression Tests ---\n";

it('accepts valid signup data', function () {
    if (SecurityUtil::validateSignupInput('example_user', 'user@example.com', 'correct-password') !== null) {
        throw new \Exception('Expected valid signup data to pass');
    }
});

it('rejects invalid signup email, username length, and short passwords', function () {
    if (SecurityUtil::validateSignupInput('user', 'not-an-email', 'correct-password') === null) {
        throw new \Exception('Expected invalid email to fail');
    }
    if (SecurityUtil::validateSignupInput(str_repeat('u', 51), 'user@example.com', 'correct-password') === null) {
        throw new \Exception('Expected oversized username to fail');
    }
    if (SecurityUtil::validateSignupInput('user', 'user@example.com', 'short') === null) {
        throw new \Exception('Expected short password to fail');
    }
});

it('sanitizes active SVG content', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script><path d="M0 0"/></svg>';
    $clean = SecurityUtil::sanitizeSVG($svg);
    if ($clean === '' || stripos($clean, '<script') !== false || stripos($clean, 'onload=') !== false || stripos($clean, '<path') === false) {
        throw new \Exception('Expected unsafe SVG content to be removed while preserving valid artwork');
    }
});

it('enforces message character limits and empty-message rules', function () {
    $handler = new TestableBaseHandler(null, 1, 'csrf');
    if (!$handler->validateContent(str_repeat('a', 5000))) {
        throw new \Exception('Expected 5000-character message to pass');
    }
    ob_start();
    $tooLong = $handler->validateContent(str_repeat('a', 5001));
    ob_end_clean();
    if ($tooLong) {
        throw new \Exception('Expected 5001-character message to fail');
    }
    ob_start();
    $empty = $handler->validateContent('');
    ob_end_clean();
    if ($empty) {
        throw new \Exception('Expected empty message without attachment to fail');
    }
    if (!$handler->validateContent('', true)) {
        throw new \Exception('Expected empty message with attachment to pass');
    }
});

it('applies independent, atomic rate-limit scopes', function () {
    $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sycs_api_test_' . bin2hex(random_bytes(8));
    $limiter = new FileRateLimiter($cacheDir);
    $first = $limiter->checkUserRateLimit(42, 1, 60, 'message');
    $second = $limiter->checkUserRateLimit(42, 1, 60, 'message');
    $otherScope = $limiter->checkUserRateLimit(42, 1, 60, 'friend');

    foreach (glob($cacheDir . DIRECTORY_SEPARATOR . 'rate_limit_*.json') ?: [] as $file) {
        unlink($file);
    }
    $cleanupFile = $cacheDir . DIRECTORY_SEPARATOR . '.last_cleanup';
    if (file_exists($cleanupFile)) {
        unlink($cleanupFile);
    }
    rmdir($cacheDir);

    if (!$first['allowed'] || $second['allowed'] || !$otherScope['allowed']) {
        throw new \Exception('Rate limit did not enforce separate action scopes');
    }
});

echo "\nPHP Tests finished: {$passed} passed, {$failed} failed.\n";
if ($failed > 0) {
    exit(1);
}
ob_end_flush();

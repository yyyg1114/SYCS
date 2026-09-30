<?php

/**
 * User-Agentや各種パラメータからUI Family (apple, android, windows, generic) を判定
 * 
 * @param string|null $userAgent 対象のUser-Agent文字列（指定しない場合は $_SERVER から取得）
 */
function detectUiFamily(?string $userAgent = null): string
{
    $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');

    if (empty($ua)) {
        return 'generic';
    }

    // Apple系 (iPhone, iPad, iPod, Macintosh)
    if (preg_match('/iPhone|iPad|iPod|Macintosh/i', $ua)) {
        return 'apple';
    }

    // Android系
    if (preg_match('/Android/i', $ua)) {
        return 'android';
    }

    // Windows系
    if (preg_match('/Windows NT/i', $ua)) {
        return 'windows';
    }

    return 'generic';
}

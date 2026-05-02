<?php

/**
 * Helper functions for SYCS
 */

function sendDiscordWebhook($webhookUrl, $username, $content, $avatarUrl = null, $attachmentPath = null, $baseUrl = '')
{
    if (!$webhookUrl) return;
    if ($baseUrl) {
        if ($avatarUrl && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            $avatarUrl = rtrim($baseUrl, '/') . '/' . ltrim($avatarUrl, '/');
        }
        if ($attachmentPath && !filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
            $absAttachment = rtrim($baseUrl, '/') . '/' . ltrim($attachmentPath, '/');
            $content .= "\n" . $absAttachment;
        }
    }
    $data = ['username' => $username . " (SYCS)", 'content' => $content];
    if ($avatarUrl) $data['avatar_url'] = $avatarUrl;
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    file_get_contents($webhookUrl, false, $context);
}

function get_http_status_code_from_headers($headers)
{
    if (!is_array($headers) || empty($headers[0])) return null;
    if (preg_match('/\s(\d{3})\s/', $headers[0], $matches)) return (int)$matches[1];
    return null;
}

function notifyRealtimeServer($type, $data)
{
    require_once __DIR__ . '/EnvLoader.php';
    $secret = getenv('REALTIME_SECRET_KEY') ?: getenv('SECRET_KEY');
    if (!$secret) return;
    $url = 'http://localhost:3000/api/notify';
    $payload = ['secret' => $secret, 'type' => $type, 'data' => $data];
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}

function sendPushNotification($userId, $payload)
{
    global $mysqli;
    require_once __DIR__ . '/EnvLoader.php';
    $secret = getenv('REALTIME_SECRET_KEY') ?: getenv('SECRET_KEY');
    if (!$secret) return;
    $url = 'http://localhost:3000/api/push';
    $stmt = $mysqli->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($sub = $res->fetch_assoc()) {
        $pushPayload = [
            'secret' => $secret,
            'subscription' => ['endpoint' => $sub['endpoint'], 'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']]],
            'payload' => $payload
        ];
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($pushPayload),
                'ignore_errors' => true
            ]
        ];
        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
    }
}

function verify_csrf(?string $token, ?string $sessionToken)
{
    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF Token']);
    }
}
function verify_token(?string $token, ?string $sessionToken)
{
    verify_csrf($token, $sessionToken);
}

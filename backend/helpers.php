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
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
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
    // SSE (Server-Sent Events) に移行済みのため、この関数は使用されません。
    // realtime-server (Node.js) への接続は廃止されました。
    // error_log("[SSE] notifyRealtimeServer called but is now a no-op: {$type}");
}


function sendPushNotification($userId, $payload)
{
    global $mysqli;
    require_once __DIR__ . '/EnvLoader.php';

    $publicKey = getenv('VAPID_PUBLIC_KEY');
    $privateKey = getenv('VAPID_PRIVATE_KEY');

    if (!$publicKey || !$privateKey) {
        error_log("VAPID keys not found in environment.");
        return;
    }

    $auth = [
        'VAPID' => [
            'subject' => 'mailto:information.sycs@gmail.com',
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ],
    ];

    try {
        require_once __DIR__ . '/vendor/autoload.php';
        $webPush = new \Minishlink\WebPush\WebPush($auth);

        $stmt = $mysqli->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();

        $hasSubscriptions = false;
        while ($sub = $res->fetch_assoc()) {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
            ]);

            $webPush->queueNotification(
                $subscription,
                json_encode($payload)
            );
            $hasSubscriptions = true;
        }

        if ($hasSubscriptions) {
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getEndpoint();
                if (!$report->isSuccess()) {
                    error_log("[Push] Message failed to sent for subscription {$endpoint}: {$report->getReason()}");
                    if ($report->isSubscriptionExpired()) {
                        $stmtDel = $mysqli->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                        $stmtDel->bind_param("s", $endpoint);
                        $stmtDel->execute();
                    }
                }
            }
        }
    } catch (\Exception $e) {
        error_log("WebPush Error: " . $e->getMessage());
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

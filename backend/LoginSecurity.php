<?php

function ensureLoginAttemptTracking($mysqli): void
{
    $columns = [
        'purpose' => 'VARCHAR(32) NOT NULL DEFAULT "password"',
        'user_id' => 'INT NULL DEFAULT NULL',
        'ip_address' => 'VARCHAR(45) NULL DEFAULT NULL',
    ];

    foreach ($columns as $column => $definition) {
        $result = $mysqli->query("SHOW COLUMNS FROM login_attempts LIKE '$column'");
        if ($result && $result->num_rows === 0) {
            $mysqli->query("ALTER TABLE login_attempts ADD COLUMN $column $definition");
        }
    }

    $indexChecks = [
        'idx_login_attempts_purpose_user_time' => 'CREATE INDEX idx_login_attempts_purpose_user_time ON login_attempts (purpose, user_id, attempted_at)',
        'idx_login_attempts_purpose_ip_time' => 'CREATE INDEX idx_login_attempts_purpose_ip_time ON login_attempts (purpose, ip_address, attempted_at)',
    ];

    foreach ($indexChecks as $name => $sql) {
        $result = $mysqli->query("SHOW INDEX FROM login_attempts WHERE Key_name = '$name'");
        if ($result && $result->num_rows === 0) {
            $mysqli->query($sql);
        }
    }
}

function recordLoginAttempt($mysqli, string $purpose, ?int $userId, ?string $ipAddress, string $identifier): void
{
    ensureLoginAttemptTracking($mysqli);
    $safePurpose = preg_replace('/[^a-zA-Z0-9_-]/', '', $purpose) ?: 'other';
    $userIdValue = $userId ?? 0;
    $ipValue = $ipAddress ?? 'unknown';
    $identifierValue = $identifier !== '' ? $identifier : 'unknown';

    $stmt = $mysqli->prepare('INSERT INTO login_attempts (purpose, user_id, ip_address, identifier) VALUES (?, ?, ?, ?)');
    if ($stmt === false) {
        error_log('login_attempts INSERT failed: ' . $mysqli->error);
        return;
    }

    $stmt->bind_param('siss', $safePurpose, $userIdValue, $ipValue, $identifierValue);
    if (!$stmt->execute()) {
        error_log('login_attempts INSERT execute failed: ' . $stmt->error);
    }
    $stmt->close();
}

function countRecentLoginAttempts($mysqli, string $purpose, ?int $userId, ?string $ipAddress, int $windowSeconds): array
{
    ensureLoginAttemptTracking($mysqli);
    $counts = ['user' => 0, 'ip' => 0];

    if ($userId !== null) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM login_attempts WHERE purpose = ? AND user_id = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)');
        if ($stmt) {
            $stmt->bind_param('sii', $purpose, $userId, $windowSeconds);
            $stmt->execute();
            $stmt->bind_result($counts['user']);
            $stmt->fetch();
            $stmt->close();
        }
    }

    if ($ipAddress !== null) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM login_attempts WHERE purpose = ? AND ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)');
        if ($stmt) {
            $stmt->bind_param('ssi', $purpose, $ipAddress, $windowSeconds);
            $stmt->execute();
            $stmt->bind_result($counts['ip']);
            $stmt->fetch();
            $stmt->close();
        }
    }

    return $counts;
}

function isLoginCodeIssueRateLimited($mysqli, int $userId, string $ipAddress, int $maxRequests = 3, int $windowSeconds = 900): array
{
    $counts = countRecentLoginAttempts($mysqli, 'code_issue', $userId, $ipAddress, $windowSeconds);
    $blocked = ($counts['user'] >= $maxRequests) || ($counts['ip'] >= $maxRequests);
    $remaining = max(0, $maxRequests - max($counts['user'], $counts['ip']));

    return [
        'allowed' => !$blocked,
        'remaining' => $remaining,
        'retry_after' => $windowSeconds,
        'user_count' => $counts['user'],
        'ip_count' => $counts['ip'],
    ];
}

function buildPendingLoginState(array $existingPending, array $newLogin): array
{
    $previousAttempts = isset($existingPending['attempts']) ? (int)$existingPending['attempts'] : 0;
    return [
        'user_id' => (int)$newLogin['user_id'],
        'username' => $newLogin['username'],
        'last_thread_id' => (int)($newLogin['last_thread_id'] ?? 0),
        'code_hash' => $newLogin['code_hash'],
        'expires' => (int)$newLogin['expires'],
        'attempts' => $previousAttempts,
    ];
}

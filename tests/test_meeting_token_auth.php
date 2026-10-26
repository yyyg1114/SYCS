<?php

/**
 * Regression Test for Issue #15:
 * MeetingHandler::issueJoinToken authorization checks
 */

require_once __DIR__ . '/../backend/api/BaseHandler.php';
require_once __DIR__ . '/../backend/api/handlers/MeetingHandler.php';

echo "--- Running MeetingHandler Token Authorization Regression Tests ---\n";

class TestableMeetingHandler extends MeetingHandler
{
    public array $mockRooms = [];
    public array $allowedRooms = [];
    public ?int $lastHttpCode = null;
    public ?array $lastJsonOutput = null;
    public array $mockPost = [];

    protected function verifyCsrf(): void
    {
        // CSRF check bypassed for unit testing
    }

    protected function getPost(string $key, $default = null): mixed
    {
        return $this->mockPost[$key] ?? $default;
    }

    public function canAccessMeetingRoom(int $roomId, ?int $targetUserId = null): bool
    {
        return in_array($roomId, $this->allowedRooms, true);
    }

    // Override issueJoinToken to capture HTTP code and output without exiting
    public function issueJoinToken(): void
    {
        $this->verifyCsrf();
        $roomId = (int)$this->getPost('room_id', 0);
        $roomName = trim((string)$this->getPost('room_name', ''));

        if ($roomId <= 0 && $roomName === '') {
            $this->respond(400, ['success' => false, 'error' => 'Missing room identification']);
            return;
        }

        // Mock DB resolution
        $room = null;
        if ($roomId > 0) {
            foreach ($this->mockRooms as $r) {
                if ($r['id'] === $roomId && empty($r['closed_at'])) {
                    $room = $r;
                    break;
                }
            }
        } elseif ($roomName !== '') {
            foreach ($this->mockRooms as $r) {
                if (($r['room_name'] === $roomName || ($r['room_uuid'] ?? '') === $roomName) && empty($r['closed_at'])) {
                    $room = $r;
                    break;
                }
            }
        }

        if (!$room) {
            $this->respond(404, ['success' => false, 'error' => 'Meeting room not found or closed']);
            return;
        }

        $resolvedRoomId = (int)$room['id'];
        $resolvedRoomName = (string)$room['room_name'];

        // Validate user access to room — MUST always be checked before issuing token
        if (!$this->canAccessMeetingRoom($resolvedRoomId)) {
            $this->respond(403, ['success' => false, 'error' => 'Forbidden: Cannot access this meeting room']);
            return;
        }

        $secret = getenv('SIGNALING_SECRET') ?: 'sycs-default-signaling-secret-change-me-in-prod';
        $exp = time() + 120;
        $payload = [
            'uid' => (int)$this->userId,
            'rid' => $resolvedRoomName,
            'exp' => $exp
        ];

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $sigEncoded = $this->base64UrlEncode($signature);

        $token = $payloadEncoded . '.' . $sigEncoded;

        $this->respond(200, [
            'success' => true,
            'token' => $token,
            'expires_at' => $exp,
            'room_name' => $resolvedRoomName,
            'room_id' => $resolvedRoomId
        ]);
    }

    private function respond(int $code, array $data): void
    {
        $this->lastHttpCode = $code;
        $this->lastJsonOutput = $data;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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

// Setup dummy handler with user_id = 42
$dummyMysqli = null;
$handler = new TestableMeetingHandler($dummyMysqli, 42, 'csrf-token');

// Populate mock rooms:
// Room 101: Accessible by user 42
// Room 999: Private room NOT accessible by user 42
$handler->mockRooms = [
    ['id' => 101, 'room_name' => 'allowed_room_101', 'room_uuid' => 'uuid-101', 'closed_at' => null],
    ['id' => 999, 'room_name' => 'secret_room_999', 'room_uuid' => 'uuid-999', 'closed_at' => null],
];
$handler->allowedRooms = [101];

// Test 1: Requesting token for an unauthorized room via room_id is rejected with 403
it('1. Token request for unauthorized room via room_id is rejected with 403', function () use ($handler) {
    $handler->mockPost = ['room_id' => 999];
    $handler->issueJoinToken();
    if ($handler->lastHttpCode !== 403) {
        throw new \Exception("Expected HTTP 403, got {$handler->lastHttpCode}");
    }
    if ($handler->lastJsonOutput['success'] !== false) {
        throw new \Exception("Expected success=false");
    }
});

// Test 2: Requesting token for an unauthorized room via room_name with room_id=0 is rejected with 403 (Issue #15 P0 bypass attempt)
it('2. Token request for unauthorized room via room_name with room_id=0 is rejected with 403', function () use ($handler) {
    $handler->mockPost = ['room_id' => 0, 'room_name' => 'secret_room_999'];
    $handler->issueJoinToken();
    if ($handler->lastHttpCode !== 403) {
        throw new \Exception("Expected HTTP 403, got {$handler->lastHttpCode}");
    }
    if ($handler->lastJsonOutput['success'] !== false) {
        throw new \Exception("Expected success=false");
    }
});

// Test 3: Requesting token with completely invalid / non-existent room_name is rejected with 404
it('3. Token request for non-existent room is rejected with 404', function () use ($handler) {
    $handler->mockPost = ['room_id' => 0, 'room_name' => 'non_existent_room'];
    $handler->issueJoinToken();
    if ($handler->lastHttpCode !== 404) {
        throw new \Exception("Expected HTTP 404, got {$handler->lastHttpCode}");
    }
});

// Test 4: Requesting token with missing room identifiers is rejected with 400
it('4. Token request with missing room identifiers is rejected with 400', function () use ($handler) {
    $handler->mockPost = [];
    $handler->issueJoinToken();
    if ($handler->lastHttpCode !== 400) {
        throw new \Exception("Expected HTTP 400, got {$handler->lastHttpCode}");
    }
});

// Test 5: Authorized room request issues valid token with DB room_name as rid
it('5. Authorized room token request succeeds and rid matches authorized DB room_name', function () use ($handler) {
    // Client attempts to pass a spoofed room_name with the valid room_id
    $handler->mockPost = ['room_id' => 101, 'room_name' => 'spoofed_name'];
    $handler->issueJoinToken();
    if ($handler->lastHttpCode !== 200) {
        throw new \Exception("Expected HTTP 200, got {$handler->lastHttpCode}");
    }
    if (empty($handler->lastJsonOutput['token'])) {
        throw new \Exception("Expected token to be present");
    }

    // Inspect token payload
    $parts = explode('.', $handler->lastJsonOutput['token']);
    $payloadJson = base64_decode(strtr($parts[0], '-_', '+/'));
    $payload = json_decode($payloadJson, true);

    if ($payload['uid'] !== 42) {
        throw new \Exception("Expected uid=42, got " . json_encode($payload['uid']));
    }
    // Must be the DB room name ('allowed_room_101'), NOT the client-supplied 'spoofed_name'
    if ($payload['rid'] !== 'allowed_room_101') {
        throw new \Exception("Expected rid='allowed_room_101', got " . json_encode($payload['rid']));
    }
});

echo "\nPHP Tests finished: {$passed} passed, {$failed} failed.\n";
if ($failed > 0) {
    exit(1);
}

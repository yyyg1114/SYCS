<?php

require_once __DIR__ . '/../backend/LoginSecurity.php';

class FakeResult
{
    public int $num_rows;

    public function __construct(int $num_rows = 0)
    {
        $this->num_rows = $num_rows;
    }
}

class FakePreparedStatement
{
    private array $boundValues = [];
    private FakeMysqli $db;
    private string $sql;
    private int $countValue;

    public function __construct(FakeMysqli $db, string $sql, int $countValue = 0)
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->countValue = $countValue;
    }

    public function bind_param(string $types, &...$values): bool
    {
        $this->boundValues = $values;
        return true;
    }

    public function execute(): bool
    {
        return true;
    }

    public function bind_result(&$value): void
    {
        $value = $this->countValue;
    }

    public function fetch(): bool
    {
        return true;
    }

    public function close(): void
    {
    }
}

class FakeMysqli
{
    public array $counts = [
        'user' => 3,
        'ip' => 3,
    ];

    public function query(string $sql): FakeResult
    {
        if (stripos($sql, 'SHOW COLUMNS') !== false) {
            return new FakeResult(1);
        }
        if (stripos($sql, 'SHOW INDEX') !== false) {
            return new FakeResult(1);
        }
        return new FakeResult(0);
    }

    public function prepare(string $sql): FakePreparedStatement
    {
        if (stripos($sql, 'SELECT COUNT(*)') !== false) {
            return new FakePreparedStatement($this, $sql, 3);
        }
        return new FakePreparedStatement($this, $sql, 0);
    }
}

$passed = 0;
$failed = 0;

function it(string $description, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  [PASS] $description\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  [FAIL] $description\n";
        echo "         " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "--- Running Login Hardening Regression Tests ---\n";

it('preserves prior verification failures during code resend', function () {
    $next = buildPendingLoginState(['attempts' => 4], [
        'user_id' => 7,
        'username' => 'alice',
        'last_thread_id' => 9,
        'code_hash' => 'newhash',
        'expires' => time() + 600,
    ]);
    if ($next['attempts'] !== 4) {
        throw new Exception('Expected previous verification attempts to survive a resend');
    }
});

it('rate-limits repeated code issuance for the same user and IP', function () {
    $db = new FakeMysqli();
    $rate = isLoginCodeIssueRateLimited($db, 42, '203.0.113.7', 3, 900);
    if ($rate['allowed']) {
        throw new Exception('Expected code issuance to be blocked at the rate limit');
    }
    if ($rate['remaining'] !== 0) {
        throw new Exception('Expected remaining count to be zero when blocked');
    }
});

it('keeps verification and password failure budgets separated', function () {
    $pending = ['attempts' => 4, 'user_id' => 99, 'code_hash' => hash('sha256', '123456')];
    if (buildPendingLoginState($pending, ['user_id' => 99, 'username' => 'bob', 'last_thread_id' => 1, 'code_hash' => hash('sha256', '654321'), 'expires' => time() + 600])['attempts'] !== 4) {
        throw new Exception('Verification attempts should not be reset when a new code is issued');
    }
});

echo "\nPHP Tests finished: {$passed} passed, {$failed} failed.\n";
if ($failed > 0) {
    exit(1);
}

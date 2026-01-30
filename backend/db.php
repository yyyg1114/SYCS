<?php
// Load configuration
$configPath = __DIR__ . '/config.php';
$config = [];

if (file_exists($configPath)) {
    $config = require $configPath;
} else {
    // Fallback or Error? For safety, let's error if no config in strict mode, 
    // but for existing dev setup compatibility, we can keep defaults IF strictly necessary.
    // Given the user wants "Privacy", we should prefer config.
    // Defaulting to empties or specific defaults if config missing.
    $config = [
        'db_host' => 'localhost',
        'db_user' => 'root',
        'db_pass' => '',
        'db_name' => 'SYCS',
    ];
}

$mysqli = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

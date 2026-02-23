<?php
require "db.php";

if (php_sapi_name() !== 'cli') {
    exit("CLI only");
}

$dir = __DIR__ . "/../migrations";
$files = scandir($dir);

$executed = [];
$res = $mysqli->query("SELECT migration FROM migrations");
while ($row = $res->fetch_assoc()) {
    $executed[] = $row['migration'];
}

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) !== "sql") continue;

    if (!in_array($file, $executed)) {

        $sql = file_get_contents($dir . "/" . $file);

        if ($mysqli->multi_query($sql)) {

            while ($mysqli->more_results() && $mysqli->next_result()) {
            }

            $stmt = $mysqli->prepare(
                "INSERT INTO migrations (migration) VALUES (?)"
            );
            $stmt->bind_param("s", $file);
            $stmt->execute();
            $stmt->close();

            echo "Migrated: $file\n";
        } else {
            echo "Error in $file: " . $mysqli->error . "\n";
            exit;
        }
    }
}

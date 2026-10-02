<?php

$config = require __DIR__ . '/app/config/config.php';
$dbConfig = $config['database'];

$pdo = new PDO(
    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
    $dbConfig['username'],
    $dbConfig['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('DESCRIBE customers');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Kolom di tabel customers:\n';
foreach ($columns as $col) {
    echo '  - ' . $col['Field'] . ' (' . $col['Type'] . ")\n";
}

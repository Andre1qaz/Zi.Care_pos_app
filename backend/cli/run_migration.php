<?php

// Migration Runner for POS Application
// Usage: php cli/run_migration.php

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../app/config/config.php';

try {
    // First connect without database to check/create it
    $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if database exists, create if not
    $dbName = $config['database']['dbname'];
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    if ($stmt->fetch() === false) {
        echo "Database '$dbName' not found. Creating it...\n";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$dbName' created.\n";
    }

    // Now connect to the specific database
    $dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Run migrations in order
    $migrations = [
        '001_initial_schema.sql',
        '002_add_product_type.sql',
        '003_fix_service_stock.sql',
        '004_partial_payment_system.sql'
    ];

    foreach ($migrations as $migrationFile) {
        $fullPath = __DIR__ . '/../../database/migrations/' . $migrationFile;
        
        if (!file_exists($fullPath)) {
            echo "Warning: Migration file not found: $migrationFile (skipping)\n";
            continue;
        }

        echo "\n--- Running migration: $migrationFile ---\n";
        
        $sql = file_get_contents($fullPath);

        // Remove USE statement since we're already connected to the database
        $sql = preg_replace('/USE\s+\w+;/', '', $sql);

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                echo "Executing: " . substr($statement, 0, 50) . "...\n";
                $pdo->exec($statement);
            }
        }

        echo "✓ Migration $migrationFile completed successfully!\n";
    }

    echo "\n✓ All migrations completed successfully!\n";
} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration in backend/.env file.\n";
    exit(1);
}

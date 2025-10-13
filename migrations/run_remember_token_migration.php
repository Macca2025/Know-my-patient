<?php
/**
 * Migration Script: Add remember_token column to users table
 * Run this script to enable "Remember Me" login functionality
 * 
 * Usage: php migrations/run_remember_token_migration.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (class_exists('Dotenv\\Dotenv')) {
    (Dotenv\Dotenv::createImmutable(__DIR__ . '/../'))->safeLoad();
}

try {
    // Create database connection
    $pdo = new \PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '8889',
            $_ENV['DB_NAME'] ?? 'know_my_patient'
        ),
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? 'root'
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";
    echo "Checking if remember_token column exists...\n";

    // Check if column exists
    $checkStmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = DATABASE()
        AND table_name = 'users'
        AND column_name = 'remember_token'
    ");
    $result = $checkStmt->fetch(\PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo "✓ remember_token column already exists. No migration needed.\n";
    } else {
        echo "Adding remember_token column...\n";
        
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL 
            AFTER verification_token_expires
        ");
        
        echo "✓ remember_token column added successfully!\n";
    }

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  Remember Me functionality is now ready to use!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "The login form will now support:\n";
    echo "  • Checking the 'Remember Me' checkbox on login\n";
    echo "  • Automatic login for 30 days using secure tokens\n";
    echo "  • Token stored securely in database (hashed with Argon2ID)\n";
    echo "  • Cookie stored with HttpOnly and Secure flags\n";
    echo "\n";

} catch (\PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check:\n";
    echo "  1. MAMP/MySQL is running\n";
    echo "  2. .env file has correct DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS\n";
    echo "  3. Database 'know_my_patient' exists\n";
    echo "\n";
    exit(1);
} catch (\Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

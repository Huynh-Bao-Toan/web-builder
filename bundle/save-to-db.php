<?php
/**
 * Script to save bundle.json to database
 * Run this after generating bundle.json with minify-bundle.js
 */

require_once './bundle/db-loader.php';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'your_username',
    'password' => 'your_password'
];

// Initialize database connection
try {
    $db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Configuration
$bundleJsonPath = __DIR__ . '/bundle.json';
$version = isset($argv[1]) ? $argv[1] : '1.0.0';
$status = isset($argv[2]) ? $argv[2] : 'draft'; // 'draft', 'published', 'archived'

if (!file_exists($bundleJsonPath)) {
    die("Error: bundle.json not found at {$bundleJsonPath}\nRun: node minify-bundle.js first\n");
}

// Initialize bundle loader
$bundleLoader = new BuilderBundleLoader($db, 'landing-page');

// Save bundle to database
echo "Saving bundle version {$version} to database...\n";

if ($bundleLoader->saveBundleFromJson($bundleJsonPath, $version, $status)) {
    echo "✓ Bundle saved successfully!\n";
    echo "  Version: {$version}\n";
    echo "  Status: {$status}\n";
    
    if ($status === 'published') {
        $bundleLoader->publishBundle($version);
        echo "  Published at: " . date('Y-m-d H:i:s') . "\n";
    }
} else {
    echo "✗ Failed to save bundle\n";
    exit(1);
}


<?php
echo "<h1>PHP Test</h1>";
echo "PHP Version: " . phpversion() . "<br>";

// Test 1: Basic PHP
echo "<h2>Test 1: Basic PHP - OK</h2>";

// Test 2: Extensions
echo "<h2>Test 2: Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅" : "❌") . "<br>";
}

// Test 3: MySQL Connection
echo "<h2>Test 3: MySQL Connection</h2>";
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "MySQL: ✅ Connected<br>";
    
    // Test database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS studymate_saas");
    echo "Database: ✅ Created<br>";
    
} catch (Exception $e) {
    echo "MySQL: ❌ " . $e->getMessage() . "<br>";
}

// Test 4: File permissions
echo "<h2>Test 4: File Access</h2>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Config file exists: " . (file_exists(__DIR__ . '/config.php') ? "✅" : "❌") . "<br>";

phpinfo();
?>
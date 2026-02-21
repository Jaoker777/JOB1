<?php
/**
 * Database Connection - Gaming Store Inventory System
 * Uses PDO with prepared statements for security
 */

$host = 'db';           // Docker service name
$dbname = 'gaming_store';
$username = 'root';
$password = 'rootpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die('<div style="padding:40px;text-align:center;font-family:sans-serif;color:#ef4444;">
        <h2>⚠️ Database Connection Failed</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p style="color:#94a3b8;">Make sure Docker containers are running: <code>docker-compose up -d</code></p>
    </div>');
}

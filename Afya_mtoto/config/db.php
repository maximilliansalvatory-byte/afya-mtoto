<?php
// config/db.php
// Afya Mtoto - Database Connection

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP password is empty
define('DB_NAME', 'afya_mtoto');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Optional: Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language & Theme Settings
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'sw'; // Default language Kiswahili
}
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light'; // Default theme
}

// Handle language change
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'sw'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Handle theme change
if (isset($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

// Load language file
$lang = require __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
?>
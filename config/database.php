<?php
// config/database.php
// Database connection configuration

define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'taskflow_db');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);

    if ($conn->connect_error) {
        die('<div style="font-family:sans-serif;padding:2rem;background:#1a1a2e;color:#ff6b9d;min-height:100vh;display:flex;align-items:center;justify-content:center;">
            <div style="text-align:center;">
                <h2 style="font-size:1.5rem;margin-bottom:1rem;">Database Connection Failed</h2>
                <p style="color:#aaa;">Please check your database credentials in <code style="color:#f59e0b;">config/database.php</code></p>
                <p style="color:#888;font-size:0.85rem;margin-top:0.5rem;">Error: ' . htmlspecialchars($conn->connect_error) . '</p>
            </div>
        </div>');
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// Session helper
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

function requireLoginRoot() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>

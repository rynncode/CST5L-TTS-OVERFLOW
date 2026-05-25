<?php
function _env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

define('DB_HOST', _env('MYSQLHOST',     'localhost'));
define('DB_PORT', _env('MYSQLPORT',     '3306'));
define('DB_USER', _env('MYSQLUSER',     'root'));
define('DB_PASS', _env('MYSQLPASSWORD', ''));
define('DB_NAME', _env('MYSQLDATABASE', 'railway'));

function getDBConnection() {
    static $conn = null;
    if ($conn !== null) return $conn;

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:2rem;background:#1a1a2e;color:#ff6b9d;min-height:100vh;display:flex;align-items:center;justify-content:center;">
            <div style="text-align:center;">
                <h2 style="font-size:1.5rem;margin-bottom:1rem;">Database Connection Failed</h2>
                <p style="color:#888;font-size:0.85rem;margin-top:0.5rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>
        </div>');
    }
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
}

function requireLoginRoot() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>

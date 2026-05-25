<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}

header('Location: ../dashboard.php');
exit();

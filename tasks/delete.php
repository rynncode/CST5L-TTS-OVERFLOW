<?php
// tasks/delete.php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

// Must be a POST cuz it prevents CSRF via GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: ../dashboard.php?deleted=1');
exit();

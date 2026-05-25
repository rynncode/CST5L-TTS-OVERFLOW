<?php
require_once '../config/database.php';
requireLogin();

$error   = '';
$raw     = [];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = [
        'title'       => $_POST['title']       ?? '',
        'description' => $_POST['description'] ?? '',
        'due_date'    => $_POST['due_date']     ?? '',
        'priority'    => $_POST['priority']     ?? 'medium',
        'status'      => $_POST['status']       ?? 'pending',
    ];

    $title       = sanitize($raw['title']);
    $description = sanitize($raw['description']);
    $priority    = sanitize($raw['priority']);
    $status      = sanitize($raw['status']);
    $due_date    = sanitize($raw['due_date']);
    $due_val     = $due_date ?: null;

    $allowed_priority = ['low', 'medium', 'high'];
    $allowed_status   = ['pending', 'in_progress', 'completed'];

    if (empty($title)) {
        $error = 'Task title is required.';
    } elseif (strlen($title) > 200) {
        $error = 'Title must be under 200 characters.';
    } elseif (!in_array($priority, $allowed_priority)) {
        $error = 'Invalid priority value.';
    } elseif (!in_array($status, $allowed_status)) {
        $error = 'Invalid status value.';
    } elseif ($due_date && !DateTime::createFromFormat('Y-m-d', $due_date)) {
        $error = 'Invalid due date format.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, priority, status, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $priority, $status, $due_val])) {
            header('Location: ../dashboard.php?added=1');
            exit();
        }
        $error = 'Failed to add task. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Task — Overflow</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <svg width="26" height="26" viewBox="0 0 32 32" fill="none" style="flex-shrink:0;">
        <defs>
          <linearGradient id="olg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#7c5cfc"/><stop offset="100%" stop-color="#a286ff"/></linearGradient>
          <linearGradient id="olg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#a286ff" stop-opacity="0.85"/><stop offset="100%" stop-color="#c4aaff" stop-opacity="0.3"/></linearGradient>
        </defs>
        <rect x="5" y="15" width="22" height="13" rx="3.5" fill="url(#olg1)" opacity="0.12" stroke="url(#olg1)" stroke-width="1.2"/>
        <path d="M5 18 Q9.5 13 16 16 Q22.5 19 27 14" stroke="url(#olg1)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
        <path d="M8 15 C8 12 9.5 10 8.5 7.5" stroke="url(#olg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
        <path d="M16 16 C16 13 17.5 11 16.5 8.5" stroke="url(#olg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
        <path d="M24 14 C24 11 25.5 9 24.5 6.5" stroke="url(#olg2)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
        <circle cx="16" cy="16" r="2" fill="url(#olg1)" opacity="0.95"/>
      </svg>
      OVER<span class="logo-dot">FLOW</span>
    </div>
    <ul class="sidebar-nav">
      <li><a href="../dashboard.php">
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2"/><rect x="9" y="1.5" width="5.5" height="5.5" rx="1.2"/><rect x="1.5" y="9" width="5.5" height="5.5" rx="1.2"/><rect x="9" y="9" width="5.5" height="5.5" rx="1.2"/></svg>
        Dashboard
      </a></li>
      <li><a href="add.php" class="active">
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/><path d="M8 5 V11 M5 8 H11"/></svg>
        New Task
      </a></li>
    </ul>
    <div class="sidebar-user" style="margin-top:auto;">
      <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="user-role"><a href="../logout-transition.php" style="color:var(--text-muted);font-size:.73rem;">Sign out</a></div>
      </div>
    </div>
  </aside>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">New Task</div>
      <a href="../dashboard.php" class="btn btn-ghost btn-sm">← Back</a>
    </div>
    <div class="page-body">
      <div class="form-card">
        <h2 class="form-heading">ADD A NEW TASK</h2>
        <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="add.php">
          <div class="form-group">
            <label class="form-label">Task Title *</label>
            <input type="text" name="title" class="form-control" placeholder="What needs to be done?" value="<?= htmlspecialchars($raw['title'] ?? '') ?>" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" placeholder="Optional details about this task…"><?= htmlspecialchars($raw['description'] ?? '') ?></textarea>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-control">
                <option value="low" <?= ($raw['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= ($raw['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= ($raw['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value="pending" <?= ($raw['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= ($raw['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= ($raw['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($raw['due_date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary" style="letter-spacing:.06em;">＋ ADD TASK</button>
            <a href="../dashboard.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>

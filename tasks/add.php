<?php
// tasks/add.php
require_once '../config/database.php';
require_once '../includes/task_validation.php';
requireLogin();

$error   = '';
$raw     = [];          // repopulation values
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Keep raw POST for form repopulation (htmlspecialchars applied at output)
    $raw = [
        'title'       => $_POST['title']       ?? '',
        'description' => $_POST['description'] ?? '',
        'due_date'    => $_POST['due_date']     ?? '',
        'priority'    => $_POST['priority']     ?? 'medium',
        'status'      => $_POST['status']       ?? 'pending',
    ];

    ['error' => $error, 'data' => $data] = validateTaskInput($_POST);

    if (!$error) {
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, title, description, priority, status, due_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssss",
            $user_id,
            $data['title'], $data['description'],
            $data['priority'], $data['status'], $data['due_val']
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: ../dashboard.php?added=1');
            exit();
        }
        $error = 'Failed to add task. Please try again.';
        $stmt->close();
        $conn->close();
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

  <?php $sidebarRoot = '../'; $activeNav = 'add'; require_once '../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">New Task</div>
      <a href="../dashboard.php" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="page-body">
      <div class="form-card">
        <h2 class="form-heading">ADD A NEW TASK</h2>

        <?php if ($error): ?>
          <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="add.php">
          <div class="form-group">
            <label class="form-label">Task Title *</label>
            <input
              type="text"
              name="title"
              class="form-control"
              placeholder="What needs to be done?"
              value="<?= htmlspecialchars($raw['title'] ?? '') ?>"
              required
              autofocus
            >
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea
              name="description"
              class="form-control"
              placeholder="Optional details about this task…"
            ><?= htmlspecialchars($raw['description'] ?? '') ?></textarea>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-control">
                <option value="low"    <?= ($raw['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= ($raw['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high"   <?= ($raw['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>High</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value="pending"     <?= ($raw['status'] ?? 'pending') === 'pending'     ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= ($raw['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed"   <?= ($raw['status'] ?? '') === 'completed'   ? 'selected' : '' ?>>Completed</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input
              type="date"
              name="due_date"
              class="form-control"
              value="<?= htmlspecialchars($raw['due_date'] ?? '') ?>"
              min="<?= date('Y-m-d') ?>"
            >
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

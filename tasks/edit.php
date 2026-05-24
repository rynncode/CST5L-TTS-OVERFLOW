<?php
// tasks/edit.php
require_once '../config/database.php';
require_once '../includes/task_validation.php';
requireLogin();

$error   = '';
$user_id = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    $conn->close();
    header('Location: ../dashboard.php');
    exit();
}

// Default repopulation to existing task values
$raw = [
    'title'       => $task['title'],
    'description' => $task['description'],
    'due_date'    => $task['due_date'],
    'priority'    => $task['priority'],
    'status'      => $task['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Override with POST values for repopulation
    $raw = [
        'title'       => $_POST['title']       ?? $task['title'],
        'description' => $_POST['description'] ?? $task['description'],
        'due_date'    => $_POST['due_date']     ?? $task['due_date'],
        'priority'    => $_POST['priority']     ?? $task['priority'],
        'status'      => $_POST['status']       ?? $task['status'],
    ];

    ['error' => $error, 'data' => $data] = validateTaskInput($_POST, $task);

    if (!$error) {
        $upd = $conn->prepare(
            "UPDATE tasks SET title=?, description=?, priority=?, status=?, due_date=?
             WHERE id=? AND user_id=?"
        );
        $upd->bind_param("sssssii",
            $data['title'], $data['description'],
            $data['priority'], $data['status'], $data['due_val'],
            $id, $user_id
        );

        if ($upd->execute()) {
            $upd->close();
            $conn->close();
            header('Location: ../dashboard.php?updated=1');
            exit();
        }
        $error = 'Failed to update task.';
        $upd->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Task — Overflow</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">

  <?php $sidebarRoot = '../'; $activeNav = ''; require_once '../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Edit Task</div>
      <a href="../dashboard.php" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="page-body">
      <div class="form-card">
        <h2 class="form-heading">EDIT TASK</h2>

        <?php if ($error): ?>
          <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit.php?id=<?= $id ?>">
          <div class="form-group">
            <label class="form-label">Task Title *</label>
            <input
              type="text"
              name="title"
              class="form-control"
              value="<?= htmlspecialchars($raw['title']) ?>"
              required
              autofocus
            >
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($raw['description']) ?></textarea>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-control">
                <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label): ?>
                  <option value="<?= $val ?>" <?= $raw['priority'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <?php foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $val => $label): ?>
                  <option value="<?= $val ?>" <?= $raw['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input
              type="date"
              name="due_date"
              class="form-control"
              value="<?= htmlspecialchars($raw['due_date']) ?>"
            >
          </div>

          <div class="form-footer">
            <button type="submit" class="btn btn-primary" style="letter-spacing:.06em;">SAVE CHANGES</button>
            <a href="view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>

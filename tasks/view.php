<?php
// tasks/view.php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$task) {
    header('Location: ../dashboard.php');
    exit();
}

$today       = date('Y-m-d');
$statusLabel = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];

// Handle quick status — POST only to prevent CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $qs      = sanitize($_POST['quick_status']);
    $allowed = ['pending', 'in_progress', 'completed'];
    if (in_array($qs, $allowed)) {
        $conn2 = getDBConnection();
        $u = $conn2->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
        $u->bind_param("sii", $qs, $id, $user_id);
        $u->execute();
        $u->close();
        $conn2->close();
        header('Location: view.php?id=' . $id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($task['title']) ?> — Overflow</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">

  <?php $sidebarRoot = '../'; $activeNav = ''; require_once '../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Task Detail</div>
      <a href="../dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>

    <div class="page-body">
      <div class="form-card" style="max-width:700px;">

        <!-- Title row -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;">
          <h1 style="font-family:var(--font-display);font-size:1.6rem;letter-spacing:.05em;color:var(--text);<?= $task['status']==='completed'?'text-decoration:line-through;opacity:.5;':'' ?>">
            <?= htmlspecialchars($task['title']) ?>
          </h1>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="edit.php?id=<?= $task['id'] ?>" class="btn btn-warning btn-sm">✎ Edit</a>
            <form method="POST" action="delete.php?id=<?= $task['id'] ?>" style="display:inline;" onsubmit="return confirm('Delete this task permanently?')">
              <button type="submit" class="btn btn-danger btn-sm">✕ Delete</button>
            </form>
          </div>
        </div>

        <!-- Meta grid -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.85rem;margin-bottom:1.75rem;">
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:10px;padding:1rem;">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.4rem;">Status</div>
            <span class="badge badge-<?= $task['status'] ?>"><?= $statusLabel[$task['status']] ?></span>
          </div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:10px;padding:1rem;">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.4rem;">Priority</div>
            <span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
          </div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:10px;padding:1rem;">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.4rem;">Due Date</div>
            <?php if ($task['due_date']): ?>
              <?php
                $dueCls = 'color:var(--text)';
                if ($task['due_date'] < $today && $task['status'] !== 'completed') $dueCls = 'color:var(--danger);font-weight:500;';
                elseif ($task['due_date'] <= date('Y-m-d', strtotime('+3 days')) && $task['status'] !== 'completed') $dueCls = 'color:var(--warning);';
              ?>
              <span style="font-size:.88rem;<?= $dueCls ?>"><?= date('M j, Y', strtotime($task['due_date'])) ?></span>
            <?php else: ?>
              <span style="color:var(--text-dim);font-size:.88rem;">No due date</span>
            <?php endif; ?>
          </div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:10px;padding:1rem;">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.4rem;">Created</div>
            <span style="font-size:.88rem;color:var(--text);"><?= date('M j, Y', strtotime($task['created_at'])) ?></span>
          </div>
        </div>

        <!-- Description -->
        <?php if ($task['description']): ?>
          <div style="margin-bottom:1.75rem;">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.6rem;">Description</div>
            <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:10px;padding:1.1rem;font-size:.9rem;line-height:1.75;color:var(--text);white-space:pre-wrap;"><?= htmlspecialchars($task['description']) ?></div>
          </div>
        <?php endif; ?>

        <!-- Quick status -->
        <div style="border-top:1px solid var(--border);padding-top:1.25rem;">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:.75rem;">Change Status</div>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <?php foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $s => $l): ?>
              <?php if ($task['status'] === $s): ?>
                <span class="btn btn-sm btn-primary" style="pointer-events:none;opacity:.7;letter-spacing:.06em;"><?= strtoupper($l) ?></span>
              <?php else: ?>
                <form method="POST" action="view.php?id=<?= $task['id'] ?>" style="margin:0;">
                  <input type="hidden" name="quick_status" value="<?= $s ?>">
                  <button type="submit" class="btn btn-sm btn-ghost"><?= strtoupper($l) ?></button>
                </form>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>

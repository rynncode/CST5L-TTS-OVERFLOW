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

$today = date('Y-m-d');
$statusLabel = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];

// Handle quick status — POST only to prevent CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $qs = sanitize($_POST['quick_status']);
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

  <aside class="sidebar">
    <div class="sidebar-logo">
      <svg width="26" height="26" viewBox="0 0 32 32" fill="none" style="flex-shrink:0;">
        <defs>
          <linearGradient id="olg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#7c5cfc"/><stop offset="100%" stop-color="#a286ff"/>
          </linearGradient>
          <linearGradient id="olg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#a286ff" stop-opacity="0.85"/><stop offset="100%" stop-color="#c4aaff" stop-opacity="0.3"/>
          </linearGradient>
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
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2"/>
          <rect x="9" y="1.5" width="5.5" height="5.5" rx="1.2"/>
          <rect x="1.5" y="9" width="5.5" height="5.5" rx="1.2"/>
          <rect x="9" y="9" width="5.5" height="5.5" rx="1.2"/>
        </svg>
        Dashboard
      </a></li>
      <li><a href="add.php">
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/>
          <path d="M8 5 V11 M5 8 H11"/>
        </svg>
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

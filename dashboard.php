<?php
require_once 'config/database.php';
requireLoginRoot();

$conn     = getDBConnection();
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid  = (int)$_POST['toggle_id'];
    $stmt = $conn->prepare("UPDATE tasks SET status = CASE WHEN status='completed' THEN 'pending' ELSE 'completed' END WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $tid, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    exit();
}

$search   = sanitize($_GET['search']   ?? '');
$status   = sanitize($_GET['status']   ?? '');
$priority = sanitize($_GET['priority'] ?? '');

$where  = ["t.user_id = ?"];
$params = [$user_id];
$types  = "i";

if ($search !== '') {
    $where[]  = "(t.title LIKE ? OR t.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like;
    $types   .= "ss";
}
if ($status !== '') { $where[] = "t.status = ?"; $params[] = $status; $types .= "s"; }
if ($priority !== '') { $where[] = "t.priority = ?"; $params[] = $priority; $types .= "s"; }

$whereSQL = implode(' AND ', $where);
$sql = "SELECT * FROM tasks t WHERE $whereSQL ORDER BY FIELD(t.priority,'high','medium','low'), t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statsQ = $conn->prepare("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='in_progress') AS in_progress, SUM(status='completed') AS completed FROM tasks WHERE user_id=?");
$statsQ->bind_param("i", $user_id);
$statsQ->execute();
$stats = $statsQ->get_result()->fetch_assoc();
$statsQ->close();
// ── Due & Late tasks widget query ──
$dueQ = $conn->prepare("
    SELECT id, title, description, priority, status, due_date,
           DATEDIFF(due_date, CURDATE()) AS days_left
    FROM tasks
    WHERE user_id = ?
      AND status != 'completed'
      AND due_date IS NOT NULL
    ORDER BY due_date ASC
    LIMIT 10
");
$dueQ->bind_param("i", $user_id);
$dueQ->execute();
$dueTasks = $dueQ->get_result()->fetch_all(MYSQLI_ASSOC);
$dueQ->close();
$conn->close();

$today         = date('Y-m-d');
$overdueTasks  = array_filter($dueTasks, fn($t) => $t['due_date'] < $today);
$dueSoonTasks  = array_filter($dueTasks, fn($t) => $t['due_date'] >= $today && $t['days_left'] <= 3);
$upcomingTasks = array_filter($dueTasks, fn($t) => $t['days_left'] > 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Overflow</title>
  <link rel="stylesheet" href="css/style.css">
  <style>

    /* ── Due & Late Widget ── */
    .due-widget {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    .due-widget-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--border);
      gap: 1rem;
      flex-wrap: wrap;
      background: var(--bg-elevated);
    }
    .due-widget-title {
      font-family: var(--font-display);
      font-size: .95rem;
      letter-spacing: .08em;
      color: var(--text);
    }
    .due-badge-overdue {
      font-size: .68rem; font-weight: 600; letter-spacing: .05em;
      padding: .18rem .55rem; border-radius: 50px;
      background: rgba(255,95,106,.12); color: var(--danger);
      border: 1px solid rgba(255,95,106,.25);
    }
    .due-badge-soon {
      font-size: .68rem; font-weight: 600; letter-spacing: .05em;
      padding: .18rem .55rem; border-radius: 50px;
      background: rgba(244,163,58,.1); color: var(--warning);
      border: 1px solid rgba(244,163,58,.22);
    }
    .due-tabs {
      display: flex;
      gap: .25rem;
    }
    .due-tab {
      padding: .3rem .75rem;
      border-radius: 6px;
      font-family: var(--font-body);
      font-size: .78rem;
      font-weight: 500;
      border: 1px solid transparent;
      background: transparent;
      color: var(--text-muted);
      cursor: pointer;
      transition: all .15s;
    }
    .due-tab:hover { color: var(--text); background: var(--bg-card); }
    .due-tab.active {
      background: var(--accent-dim);
      color: var(--accent);
      border-color: var(--border-accent);
    }
    .due-list { display: flex; flex-direction: column; }
    .due-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: .85rem 1.25rem;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
      position: relative;
    }
    .due-row:last-child { border-bottom: none; }
    .due-row:hover { background: rgba(162,134,255,.025); }
    .due-row[style*="display:none"] { display: none !important; }

    /* Left urgency bar */
    .due-urgency-bar {
      position: absolute;
      left: 0; top: 10%; bottom: 10%;
      width: 3px; border-radius: 0 2px 2px 0;
    }
    .due-urgency-bar.overdue  { background: var(--danger); }
    .due-urgency-bar.soon     { background: var(--warning); }
    .due-urgency-bar.upcoming { background: var(--accent); }

    .due-row-main { flex: 1; min-width: 0; padding-left: .4rem; }
    .due-row-title { font-size: .88rem; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .due-row-title.done { text-decoration: line-through; opacity: .4; }
    .due-row-desc  { font-size: .76rem; color: var(--text-muted); margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Days indicator chip */
    .due-days {
      display: flex;
      align-items: center;
      gap: .35rem;
      font-size: .78rem;
      font-weight: 600;
      padding: .28rem .7rem;
      border-radius: 50px;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .due-days.overdue  { background: rgba(255,95,106,.1);  color: var(--danger);  border: 1px solid rgba(255,95,106,.2); }
    .due-days.soon     { background: rgba(244,163,58,.1);  color: var(--warning); border: 1px solid rgba(244,163,58,.2); }
    .due-days.upcoming { background: rgba(162,134,255,.08); color: var(--accent); border: 1px solid rgba(162,134,255,.18); }

    /* ── Icon system ── */
    .ico { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
    .ico svg { display:block; }

    /* Sidebar icon wrappers — defined in style.css */

    /* Action icon buttons — replaces .btn btn-ghost btn-sm */
    .act-btn {
      display:inline-flex; align-items:center; justify-content:center;
      width:30px; height:30px;
      border-radius:7px;
      border:1px solid var(--border);
      background:transparent;
      cursor:pointer; transition:all .18s;
      text-decoration:none; flex-shrink:0;
      color:var(--text-muted);
    }
    .act-btn:hover   { border-color:var(--border-light); background:var(--bg-elevated); color:var(--text); }
    .act-btn.warn    { border-color:rgba(244,163,58,.25); color:var(--warning); }
    .act-btn.warn:hover  { background:rgba(244,163,58,.1); }
    .act-btn.danger  { border-color:rgba(255,95,106,.25); color:var(--danger); }
    .act-btn.danger:hover{ background:rgba(255,95,106,.1); }
    .act-btn.ok      { border-color:rgba(45,212,160,.25); color:var(--success); }
    .act-btn.ok:hover    { background:rgba(45,212,160,.1); }

    /* Stat card icon containers */
    .stat-ico {
      width:40px; height:40px; border-radius:11px;
      display:flex; align-items:center; justify-content:center;
      flex-shrink:0;
    }
    .stat-ico.purple { background:rgba(162,134,255,.1); }
    .stat-ico.orange { background:rgba(244,163,58,.1); }
    .stat-ico.blue   { background:rgba(79,195,247,.1); }
    .stat-ico.green  { background:rgba(45,212,160,.1); }

    /* Topbar search icon tweak */
    .search-ico-wrap {
      position:absolute; left:.85rem; top:50%; transform:translateY(-50%);
      pointer-events:none;
    }
    .search-bar input { padding-left:2.3rem !important; }

    /* Empty state icon */
    .empty-ico-box {
      width:48px; height:48px; border-radius:13px;
      background:var(--bg-elevated); border:1px solid var(--border);
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 1rem;
    }
  </style>
</head>
<body>
<div class="app-layout">

  <!-- ═══ SIDEBAR ═══════════════════════════════════════════ -->
  <aside class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
      <svg width="26" height="26" viewBox="0 0 32 32" fill="none">
        <defs>
          <linearGradient id="lg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#7c5cfc"/><stop offset="100%" stop-color="#a286ff"/>
          </linearGradient>
          <linearGradient id="lg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#a286ff" stop-opacity=".85"/><stop offset="100%" stop-color="#c4aaff" stop-opacity=".3"/>
          </linearGradient>
        </defs>
        <rect x="5" y="15" width="22" height="13" rx="3.5" fill="url(#lg1)" opacity=".12" stroke="url(#lg1)" stroke-width="1.2"/>
        <path d="M5 18 Q9.5 13 16 16 Q22.5 19 27 14" stroke="url(#lg1)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
        <path d="M8 15 C8 12 9.5 10 8.5 7.5" stroke="url(#lg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
        <path d="M16 16 C16 13 17.5 11 16.5 8.5" stroke="url(#lg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
        <path d="M24 14 C24 11 25.5 9 24.5 6.5" stroke="url(#lg2)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
        <circle cx="16" cy="16" r="2" fill="url(#lg1)" opacity=".95"/>
      </svg>
      OVER<span class="logo-dot">FLOW</span>
    </div>

    <!-- Nav -->
    <p class="sidebar-section">Navigation</p>
    <ul class="sidebar-nav">

      <li><a href="dashboard.php" class="<?= (!$status && !$priority && !$search) ? 'active' : '' ?>">
        <!-- Dashboard: four-square grid -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2"/>
          <rect x="9"   y="1.5" width="5.5" height="5.5" rx="1.2"/>
          <rect x="1.5" y="9"   width="5.5" height="5.5" rx="1.2"/>
          <rect x="9"   y="9"   width="5.5" height="5.5" rx="1.2"/>
        </svg>
        Dashboard
      </a></li>

      <li><a href="tasks/add.php">
        <!-- New task: plus inside a rounded square -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/>
          <path d="M8 5 V11 M5 8 H11"/>
        </svg>
        New Task
      </a></li>

      <li><a href="dashboard.php?status=pending" class="<?= $status==='pending' ? 'active' : '' ?>">
        <!-- Pending: circle with dashed stroke and center dot -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <circle cx="8" cy="8" r="5.5" stroke-dasharray="2.8 2"/>
          <circle cx="8" cy="8" r="1.8" fill="currentColor" stroke="none"/>
        </svg>
        Pending
      </a></li>

      <li><a href="dashboard.php?status=in_progress" class="<?= $status==='in_progress' ? 'active' : '' ?>">
        <!-- In Progress: half-filled circle -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M8 2.5 A5.5 5.5 0 0 1 8 13.5 Z" fill="currentColor"/>
        </svg>
        In Progress
      </a></li>

      <li><a href="dashboard.php?status=completed" class="<?= $status==='completed' ? 'active' : '' ?>">
        <!-- Completed: circle with checkmark -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="8" cy="8" r="5.5"/>
          <path d="M5.2 8 L7.2 10 L11 6"/>
        </svg>
        Completed
      </a></li>

    </ul>

    <p class="sidebar-section">Priority</p>
    <ul class="sidebar-nav">

      <li><a href="dashboard.php?priority=high" class="<?= $priority==='high' ? 'active' : '' ?>">
        <!-- High: three ascending bars, tallest right -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <rect x="1.5" y="9"   width="3.5" height="5.5" rx=".8" fill="currentColor" stroke="none" opacity=".35"/>
          <rect x="6.3" y="5.5" width="3.5" height="9"   rx=".8" fill="currentColor" stroke="none" opacity=".6"/>
          <rect x="11"  y="1.5" width="3.5" height="13"  rx=".8" fill="currentColor" stroke="none"/>
        </svg>
        High
      </a></li>

      <li><a href="dashboard.php?priority=medium" class="<?= $priority==='medium' ? 'active' : '' ?>">
        <!-- Medium: three equal horizontal lines -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
          <line x1="2" y1="5"  x2="14" y2="5"/>
          <line x1="2" y1="8"  x2="14" y2="8"/>
          <line x1="2" y1="11" x2="14" y2="11"/>
        </svg>
        Medium
      </a></li>

      <li><a href="dashboard.php?priority=low" class="<?= $priority==='low' ? 'active' : '' ?>">
        <!-- Low: three ascending bars, tallest left -->
        <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <rect x="1.5" y="1.5" width="3.5" height="13"  rx=".8" fill="currentColor" stroke="none"/>
          <rect x="6.3" y="5.5" width="3.5" height="9"   rx=".8" fill="currentColor" stroke="none" opacity=".6"/>
          <rect x="11"  y="9"   width="3.5" height="5.5" rx=".8" fill="currentColor" stroke="none" opacity=".35"/>
        </svg>
        Low
      </a></li>

    </ul>

    <!-- User -->
    <div class="sidebar-user">
      <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($username) ?></div>
        <div class="user-role">
          <a href="logout-transition.php" style="color:var(--text-muted);font-size:.73rem;display:inline-flex;align-items:center;gap:.3rem;">
            <!-- Sign out: door with arrow -->
            <svg width="11" height="11" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
              <path d="M5 2 H2.5 C2 2 1.5 2.5 1.5 3 V11 C1.5 11.5 2 12 2.5 12 H5"/>
              <path d="M9.5 4.5 L12 7 L9.5 9.5"/>
              <line x1="5" y1="7" x2="12" y2="7"/>
            </svg>
            Sign out
          </a>
        </div>
      </div>
    </div>
  </aside>

  <!-- ═══ MAIN ════════════════════════════════════════════════ -->
  <div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-title">Dashboard</div>

      <form method="GET" action="dashboard.php" style="display:contents;">
        <div class="search-bar" style="position:relative;">
          <span class="search-ico-wrap">
            <!-- Search: magnifier -->
            <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="var(--text-dim)" stroke-width="1.4" stroke-linecap="round">
              <circle cx="6" cy="6" r="4.2"/>
              <line x1="9.2" y1="9.2" x2="12.5" y2="12.5"/>
            </svg>
          </span>
          <input type="search" name="search" placeholder="Search tasks…"
            value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
        </div>
      </form>

      <a href="tasks/add.php" class="btn btn-primary btn-sm" style="letter-spacing:.06em;gap:.4rem;">
        <!-- Plus -->
        <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round">
          <line x1="6" y1="1.5" x2="6" y2="10.5"/>
          <line x1="1.5" y1="6" x2="10.5" y2="6"/>
        </svg>
        NEW TASK
      </a>
    </div>

    <div class="page-body">

      <!-- ── Stats ── -->
      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-ico purple">
            <!-- Total tasks: stacked layers -->
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#a286ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 14 L10 17.5 L18 14"/>
              <path d="M2 10 L10 13.5 L18 10"/>
              <path d="M2 6  L10 2.5  L18 6 L10 9.5 Z"/>
            </svg>
          </div>
          <div>
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-label">Total Tasks</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-ico orange">
            <!-- Pending: hourglass -->
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#f4a33a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 2 H15"/>
              <path d="M5 18 H15"/>
              <path d="M5 2 Q5 10 10 10 Q15 10 15 18"/>
              <path d="M15 2 Q15 10 10 10 Q5 10 5 18"/>
              <line x1="7" y1="14.5" x2="13" y2="14.5" stroke-width="1.2"/>
            </svg>
          </div>
          <div>
            <div class="stat-value"><?= (int)$stats['pending'] ?></div>
            <div class="stat-label">Pending</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-ico blue">
            <!-- In Progress: spinning arrows -->
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#4fc3f7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 10 A7 7 0 0 1 14.5 4.5"/>
              <path d="M12.5 2.5 L14.5 4.5 L12.5 6.5"/>
              <path d="M17 10 A7 7 0 0 1 5.5 15.5"/>
              <path d="M7.5 17.5 L5.5 15.5 L7.5 13.5"/>
            </svg>
          </div>
          <div>
            <div class="stat-value"><?= (int)$stats['in_progress'] ?></div>
            <div class="stat-label">In Progress</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-ico green">
            <!-- Completed: diamond with check -->
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#2dd4a0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10 2 L18 10 L10 18 L2 10 Z"/>
              <path d="M6.5 10 L9 12.5 L13.5 7.5"/>
            </svg>
          </div>
          <div>
            <div class="stat-value"><?= (int)$stats['completed'] ?></div>
            <div class="stat-label">Completed</div>
          </div>
        </div>

      </div>


      <!-- ── Due & Late Widget ── -->
      <?php if (!empty($dueTasks)): ?>
      <div class="due-widget" id="dueWidget">

        <!-- Widget header -->
        <div class="due-widget-head">
          <div style="display:flex;align-items:center;gap:.6rem;">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="color:var(--warning);opacity:.9;">
              <circle cx="8" cy="8" r="6"/>
              <line x1="8" y1="5" x2="8" y2="8.5"/>
              <circle cx="8" cy="11" r=".8" fill="currentColor" stroke="none"/>
            </svg>
            <span class="due-widget-title">UPCOMING &amp; OVERDUE</span>
            <?php if (!empty($overdueTasks)): ?>
              <span class="due-badge-overdue"><?= count($overdueTasks) ?> overdue</span>
            <?php endif; ?>
            <?php if (!empty($dueSoonTasks)): ?>
              <span class="due-badge-soon"><?= count($dueSoonTasks) ?> due soon</span>
            <?php endif; ?>
          </div>

          <!-- Sort tabs -->
          <div class="due-tabs">
            <button class="due-tab active" onclick="filterDue('all', this)">All</button>
            <button class="due-tab" onclick="filterDue('overdue', this)">Overdue</button>
            <button class="due-tab" onclick="filterDue('soon', this)">Due Soon</button>
            <button class="due-tab" onclick="filterDue('upcoming', this)">Upcoming</button>
          </div>
        </div>

        <!-- Task rows -->
        <div class="due-list">
          <?php foreach ($dueTasks as $dt):
            $daysLeft  = (int)$dt['days_left'];
            $isOverdue = $dt['due_date'] < $today;
            $isSoon    = !$isOverdue && $daysLeft <= 3;
            $urgency   = $isOverdue ? 'overdue' : ($isSoon ? 'soon' : 'upcoming');

            if ($isOverdue) {
              $daysAbs  = abs($daysLeft);
              $dueLabel = $daysAbs === 0 ? 'Was due today' : $daysAbs . 'd overdue';
            } elseif ($daysLeft === 0) {
              $dueLabel = 'Due today';
            } elseif ($daysLeft === 1) {
              $dueLabel = 'Due tomorrow';
            } else {
              $dueLabel = 'In ' . $daysLeft . 'd';
            }
          ?>
          <div class="due-row" data-urgency="<?= $urgency ?>">

            <!-- Urgency bar -->
            <div class="due-urgency-bar <?= $urgency ?>"></div>

            <!-- Task info -->
            <div class="due-row-main">
              <div class="due-row-title <?= $task['status']==='completed'?'done':'' ?>">
                <?= htmlspecialchars($dt['title']) ?>
              </div>
              <?php if ($dt['description']): ?>
                <div class="due-row-desc"><?= htmlspecialchars(mb_strimwidth($dt['description'], 0, 60, '…')) ?></div>
              <?php endif; ?>
            </div>

            <!-- Priority badge -->
            <span class="badge badge-<?= $dt['priority'] ?>" style="flex-shrink:0;"><?= ucfirst($dt['priority']) ?></span>

            <!-- Days indicator -->
            <div class="due-days <?= $urgency ?>">
              <svg width="12" height="12" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
                <?php if ($isOverdue): ?>
                  <!-- Warning triangle -->
                  <path d="M7 2 L13 12 H1 Z"/>
                  <line x1="7" y1="6" x2="7" y2="9"/>
                  <circle cx="7" cy="11" r=".6" fill="currentColor" stroke="none"/>
                <?php elseif ($isSoon): ?>
                  <!-- Clock -->
                  <circle cx="7" cy="7" r="5"/>
                  <line x1="7" y1="4.5" x2="7" y2="7"/>
                  <line x1="7" y1="7" x2="9.5" y2="7"/>
                <?php else: ?>
                  <!-- Calendar -->
                  <rect x="2" y="3" width="10" height="9" rx="1.5"/>
                  <line x1="2" y1="6.5" x2="12" y2="6.5"/>
                  <line x1="5" y1="1.5" x2="5" y2="4.5"/>
                  <line x1="9" y1="1.5" x2="9" y2="4.5"/>
                <?php endif; ?>
              </svg>
              <?= $dueLabel ?>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:.3rem;flex-shrink:0;">
              <a href="tasks/edit.php?id=<?= $dt['id'] ?>" class="act-btn warn" title="Edit">
                <svg width="12" height="12" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9.5 2.5 L11.5 4.5 L5 11 L2.5 11.5 L3 9 Z"/>
                  <line x1="8" y1="4" x2="10" y2="6"/>
                </svg>
              </a>
              <form method="POST" action="dashboard.php" style="margin:0;">
                <input type="hidden" name="toggle_id" value="<?= $dt['id'] ?>">
                <button type="submit" class="act-btn ok" title="Mark complete">
                  <svg width="12" height="12" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="7" cy="7" r="5"/>
                    <path d="M4.5 7 L6.3 8.8 L9.5 5.5"/>
                  </svg>
                </button>
              </form>
            </div>

          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── Task list header ── -->
      <div class="section-header">
        <div class="section-title" style="display:flex;align-items:center;gap:.6rem;">
          <!-- Tasks list icon -->
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.5;">
            <line x1="5" y1="4" x2="14" y2="4"/>
            <line x1="5" y1="8" x2="14" y2="8"/>
            <line x1="5" y1="12" x2="11" y2="12"/>
            <rect x="1.5" y="3" width="2" height="2" rx=".4"/>
            <rect x="1.5" y="7" width="2" height="2" rx=".4"/>
            <rect x="1.5" y="11" width="2" height="2" rx=".4"/>
          </svg>
          TASKS
          <?php if ($search || $status || $priority): ?>
            <span style="font-size:.8rem;font-weight:400;font-family:var(--font-body);color:var(--text-muted);text-transform:none;letter-spacing:0;">— filtered</span>
            <a href="dashboard.php" style="font-size:.76rem;font-family:var(--font-body);">Clear</a>
          <?php endif; ?>
        </div>

        <form method="GET" action="dashboard.php" style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"     <?= $status==='pending'     ?'selected':'' ?>>Pending</option>
            <option value="in_progress" <?= $status==='in_progress' ?'selected':'' ?>>In Progress</option>
            <option value="completed"   <?= $status==='completed'   ?'selected':'' ?>>Completed</option>
          </select>
          <select name="priority" class="filter-select" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <option value="high"   <?= $priority==='high'   ?'selected':'' ?>>High</option>
            <option value="medium" <?= $priority==='medium' ?'selected':'' ?>>Medium</option>
            <option value="low"    <?= $priority==='low'    ?'selected':'' ?>>Low</option>
          </select>
        </form>
      </div>

      <!-- ── Task table ── -->
      <div class="card">
        <?php if (empty($tasks)): ?>
          <div class="empty-state">
            <div class="empty-ico-box">
              <!-- Empty inbox: tray with nothing -->
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-dim)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12 L3 19 C3 20 3.9 21 5 21 L19 21 C20.1 21 21 20 21 19 L21 12"/>
                <path d="M3 12 L7 6 L17 6 L21 12 Z"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
              </svg>
            </div>
            <div class="empty-title">NO TASKS FOUND</div>
            <div class="empty-text" style="margin-bottom:1.25rem;">
              <?= ($search || $status || $priority) ? 'Try adjusting your filters.' : 'Create your first task to get started.' ?>
            </div>
            <?php if (!$search && !$status && !$priority): ?>
              <a href="tasks/add.php" class="btn btn-primary btn-sm" style="letter-spacing:.06em;gap:.4rem;">
                <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round">
                  <line x1="6" y1="1.5" x2="6" y2="10.5"/>
                  <line x1="1.5" y1="6" x2="10.5" y2="6"/>
                </svg>
                ADD TASK
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Task</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Due Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                  <td>
                    <div class="task-title" style="<?= $task['status']==='completed' ? 'text-decoration:line-through;opacity:.4;' : '' ?>">
                      <?= htmlspecialchars($task['title']) ?>
                    </div>
                    <?php if ($task['description']): ?>
                      <div class="task-desc"><?= htmlspecialchars($task['description']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></td>
                  <td><span class="badge badge-<?= $task['status'] ?>"><?= ucwords(str_replace('_', ' ', $task['status'])) ?></span></td>
                  <td>
                    <?php if ($task['due_date']): ?>
                      <?php
                        $cls = 'due-ok';
                        if ($task['due_date'] < $today && $task['status'] !== 'completed') $cls = 'due-overdue';
                        elseif ($task['due_date'] <= date('Y-m-d', strtotime('+3 days')) && $task['status'] !== 'completed') $cls = 'due-soon';
                      ?>
                      <span class="<?= $cls ?>"><?= date('M j, Y', strtotime($task['due_date'])) ?></span>
                    <?php else: ?>
                      <span style="color:var(--text-dim);">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="actions">

                      <!-- Toggle complete -->
                      <form method="POST" action="dashboard.php" style="margin:0;">
                        <input type="hidden" name="toggle_id" value="<?= $task['id'] ?>">
                        <?php if ($task['status'] === 'completed'): ?>
                          <button type="submit" class="act-btn" title="Mark pending">
                            <!-- Undo: curved arrow -->
                            <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M2.5 7 A4.5 4.5 0 1 0 5 3"/>
                              <path d="M2.5 3.5 L5 3 L4.5 5.5"/>
                            </svg>
                          </button>
                        <?php else: ?>
                          <button type="submit" class="act-btn ok" title="Mark complete">
                            <!-- Checkmark in circle -->
                            <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                              <circle cx="7" cy="7" r="5"/>
                              <path d="M4.5 7 L6.3 8.8 L9.5 5.5"/>
                            </svg>
                          </button>
                        <?php endif; ?>
                      </form>

                      <!-- View -->
                      <a href="tasks/view.php?id=<?= $task['id'] ?>" class="act-btn" title="View">
                        <!-- Eye: lens shape with pupil -->
                        <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M1 7 Q4 2.5 7 2.5 Q10 2.5 13 7 Q10 11.5 7 11.5 Q4 11.5 1 7 Z"/>
                          <circle cx="7" cy="7" r="1.8"/>
                        </svg>
                      </a>

                      <!-- Edit -->
                      <a href="tasks/edit.php?id=<?= $task['id'] ?>" class="act-btn warn" title="Edit">
                        <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M9.5 2.5 L11.5 4.5 L5 11 L2.5 11.5 L3 9 Z"/>
                          <line x1="8" y1="4" x2="10" y2="6"/>
                        </svg>
                      </a>

                      <!-- Delete -->
                      <form method="POST" action="tasks/delete.php?id=<?= $task['id'] ?>" style="margin:0;" onsubmit="return confirm('Delete this task?')">
                        <button type="submit" class="act-btn danger" title="Delete">
                          <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="1.5" y1="3.5" x2="12.5" y2="3.5"/>
                            <path d="M4 3.5 L4 2.5 Q4 1.5 5 1.5 L9 1.5 Q10 1.5 10 2.5 L10 3.5"/>
                            <path d="M2.5 3.5 L3.2 11.5 Q3.3 12.5 4.3 12.5 L9.7 12.5 Q10.7 12.5 10.8 11.5 L11.5 3.5"/>
                            <line x1="5.5" y1="6"  x2="5.5" y2="10.5"/>
                            <line x1="8.5" y1="6"  x2="8.5" y2="10.5"/>
                          </svg>
                        </button>
                      </form>

                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
function filterDue(type, btn) {
  // Update tab styles
  document.querySelectorAll('.due-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  // Show/hide rows
  document.querySelectorAll('.due-row').forEach(row => {
    const urgency = row.dataset.urgency;
    const show = type === 'all' || urgency === type;
    row.style.display = show ? '' : 'none';
  });

  // If no rows visible, show nothing (widget still shows header)
}
</script>
</body>
</html>

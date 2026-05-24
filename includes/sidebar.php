<?php
/**
 * includes/sidebar.php
 *
 * Shared sidebar for all app pages (dashboard, add, edit, view).
 * Requires: $_SESSION['username'] to be set before including.
 *
 * Usage (from a page in the root):       require_once 'includes/sidebar.php';
 * Usage (from a page inside tasks/):     require_once '../includes/sidebar.php';
 *
 * Optional variables the caller may define before including:
 *   $sidebarRoot  — relative path prefix back to the project root (default: '')
 *                   Set to '../' from pages inside tasks/
 *   $activeNav    — which nav item is active: 'dashboard'|'add'|'pending'|
 *                   'in_progress'|'completed'|'high'|'medium'|'low'
 */

$sidebarRoot = $sidebarRoot ?? '';
$activeNav   = $activeNav   ?? '';

// Derive active state helpers
$qStatus   = $_GET['status']   ?? '';
$qPriority = $_GET['priority'] ?? '';
$qSearch   = $_GET['search']   ?? '';

function _sidebarActive(string $key, string $activeNav, string $qStatus, string $qPriority): string {
    if ($key === $activeNav) return 'active';
    // Dashboard link is active when no filters are applied and we're on dashboard
    if ($key === 'dashboard' && $activeNav === 'dashboard' && !$qStatus && !$qPriority) return 'active';
    if ($key === $qStatus)   return 'active';
    if ($key === $qPriority) return 'active';
    return '';
}
?>
<aside class="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <svg width="26" height="26" viewBox="0 0 32 32" fill="none" style="flex-shrink:0;">
      <defs>
        <linearGradient id="slg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
          <stop offset="0%"   stop-color="#7c5cfc"/>
          <stop offset="100%" stop-color="#a286ff"/>
        </linearGradient>
        <linearGradient id="slg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
          <stop offset="0%"   stop-color="#a286ff" stop-opacity=".85"/>
          <stop offset="100%" stop-color="#c4aaff" stop-opacity=".3"/>
        </linearGradient>
      </defs>
      <rect x="5" y="15" width="22" height="13" rx="3.5" fill="url(#slg1)" opacity=".12" stroke="url(#slg1)" stroke-width="1.2"/>
      <path d="M5 18 Q9.5 13 16 16 Q22.5 19 27 14" stroke="url(#slg1)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
      <path d="M8 15 C8 12 9.5 10 8.5 7.5"  stroke="url(#slg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
      <path d="M16 16 C16 13 17.5 11 16.5 8.5" stroke="url(#slg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
      <path d="M24 14 C24 11 25.5 9 24.5 6.5"  stroke="url(#slg2)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
      <circle cx="16" cy="16" r="2" fill="url(#slg1)" opacity=".95"/>
    </svg>
    OVER<span class="logo-dot">FLOW</span>
  </div>

  <!-- Navigation -->
  <?php if ($sidebarRoot === ''): /* dashboard — show full nav */ ?>

  <p class="sidebar-section">Navigation</p>
  <ul class="sidebar-nav">
    <li><a href="<?= $sidebarRoot ?>dashboard.php" class="<?= _sidebarActive('dashboard', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2"/>
        <rect x="9"   y="1.5" width="5.5" height="5.5" rx="1.2"/>
        <rect x="1.5" y="9"   width="5.5" height="5.5" rx="1.2"/>
        <rect x="9"   y="9"   width="5.5" height="5.5" rx="1.2"/>
      </svg>
      Dashboard
    </a></li>

    <li><a href="<?= $sidebarRoot ?>tasks/add.php" class="<?= _sidebarActive('add', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/>
        <path d="M8 5 V11 M5 8 H11"/>
      </svg>
      New Task
    </a></li>

    <li><a href="<?= $sidebarRoot ?>dashboard.php?status=pending" class="<?= _sidebarActive('pending', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <circle cx="8" cy="8" r="5.5" stroke-dasharray="2.8 2"/>
        <circle cx="8" cy="8" r="1.8" fill="currentColor" stroke="none"/>
      </svg>
      Pending
    </a></li>

    <li><a href="<?= $sidebarRoot ?>dashboard.php?status=in_progress" class="<?= _sidebarActive('in_progress', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.4"/>
        <path d="M8 2.5 A5.5 5.5 0 0 1 8 13.5 Z" fill="currentColor"/>
      </svg>
      In Progress
    </a></li>

    <li><a href="<?= $sidebarRoot ?>dashboard.php?status=completed" class="<?= _sidebarActive('completed', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="8" cy="8" r="5.5"/>
        <path d="M5.2 8 L7.2 10 L11 6"/>
      </svg>
      Completed
    </a></li>
  </ul>

  <p class="sidebar-section">Priority</p>
  <ul class="sidebar-nav">
    <li><a href="<?= $sidebarRoot ?>dashboard.php?priority=high" class="<?= _sidebarActive('high', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <rect x="1.5" y="9"   width="3.5" height="5.5" rx=".8" fill="currentColor" stroke="none" opacity=".35"/>
        <rect x="6.3" y="5.5" width="3.5" height="9"   rx=".8" fill="currentColor" stroke="none" opacity=".6"/>
        <rect x="11"  y="1.5" width="3.5" height="13"  rx=".8" fill="currentColor" stroke="none"/>
      </svg>
      High
    </a></li>

    <li><a href="<?= $sidebarRoot ?>dashboard.php?priority=medium" class="<?= _sidebarActive('medium', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <line x1="2" y1="5"  x2="14" y2="5"/>
        <line x1="2" y1="8"  x2="14" y2="8"/>
        <line x1="2" y1="11" x2="14" y2="11"/>
      </svg>
      Medium
    </a></li>

    <li><a href="<?= $sidebarRoot ?>dashboard.php?priority=low" class="<?= _sidebarActive('low', $activeNav, $qStatus, $qPriority) ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <rect x="1.5" y="1.5" width="3.5" height="13"  rx=".8" fill="currentColor" stroke="none"/>
        <rect x="6.3" y="5.5" width="3.5" height="9"   rx=".8" fill="currentColor" stroke="none" opacity=".6"/>
        <rect x="11"  y="9"   width="3.5" height="5.5" rx=".8" fill="currentColor" stroke="none" opacity=".35"/>
      </svg>
      Low
    </a></li>
  </ul>

  <?php else: /* tasks/ pages — show minimal nav */ ?>

  <ul class="sidebar-nav">
    <li><a href="<?= $sidebarRoot ?>dashboard.php">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1.5" y="1.5" width="5.5" height="5.5" rx="1.2"/>
        <rect x="9"   y="1.5" width="5.5" height="5.5" rx="1.2"/>
        <rect x="1.5" y="9"   width="5.5" height="5.5" rx="1.2"/>
        <rect x="9"   y="9"   width="5.5" height="5.5" rx="1.2"/>
      </svg>
      Dashboard
    </a></li>
    <li><a href="add.php" class="<?= $activeNav === 'add' ? 'active' : '' ?>">
      <svg class="nav-ico" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
        <rect x="1.5" y="1.5" width="13" height="13" rx="2.5"/>
        <path d="M8 5 V11 M5 8 H11"/>
      </svg>
      New Task
    </a></li>
  </ul>

  <?php endif; ?>

  <!-- User -->
  <div class="sidebar-user" style="margin-top:auto;">
    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
      <div class="user-role">
        <a href="<?= $sidebarRoot ?>logout-transition.php" style="color:var(--text-muted);font-size:.73rem;display:inline-flex;align-items:center;gap:.3rem;">
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

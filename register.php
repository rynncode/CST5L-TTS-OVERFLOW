<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email']    ?? '');
    $password  = $_POST['password']          ?? '';
    $password2 = $_POST['password2']         ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Username or email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins    = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($ins->execute([$username, $email, $hashed])) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Overflow</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

  <div class="geo-canvas">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      <line class="geo-line" x1="0" y1="600" x2="900" y2="0" style="animation-delay:.1s"/>
      <line class="geo-line" x1="1440" y1="300" x2="400" y2="900" style="animation-delay:.35s"/>
      <line class="geo-line" x1="200" y1="0" x2="1440" y2="700" style="animation-delay:.55s;stroke:rgba(162,134,255,0.07);"/>
      <circle class="geo-circle" cx="100" cy="100" r="380" style="animation-delay:.2s"/>
      <circle class="geo-circle" cx="1340" cy="800" r="300" style="animation-delay:.5s"/>
      <circle class="geo-circle" cx="720" cy="450" r="200" style="animation-delay:.9s;stroke:rgba(162,134,255,0.05);"/>
      <rect class="geo-circle" x="60" y="760" width="28" height="28" rx="4" style="animation-delay:1.1s;stroke:rgba(162,134,255,0.35);stroke-dasharray:120;"/>
      <rect class="geo-circle" x="1350" y="60" width="20" height="20" rx="3" style="animation-delay:1.3s;stroke:rgba(162,134,255,0.35);stroke-dasharray:80;"/>
      <line class="geo-line-2" x1="1380" y1="450" x2="1400" y2="450" style="animation-delay:1.6s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1390" y1="440" x2="1390" y2="460" style="animation-delay:1.6s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="50" y1="440" x2="70" y2="440" style="animation-delay:1.9s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="60" y1="430" x2="60" y2="450" style="animation-delay:1.9s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <path class="geo-line-2" d="M 30 30 L 30 80 M 30 30 L 80 30" style="animation-delay:1s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <path class="geo-line-2" d="M 1410 870 L 1410 820 M 1410 870 L 1360 870" style="animation-delay:1.2s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <circle class="geo-dot" cx="900" cy="200" r="2.5" style="animation-delay:2.1s"/>
      <circle class="geo-dot" cx="300" cy="700" r="2" style="animation-delay:2.3s;fill:rgba(162,134,255,0.7);"/>
      <circle class="geo-dot" cx="1100" cy="500" r="1.5" style="animation-delay:2.5s"/>
    </svg>
  </div>

  <div class="auth-wrap">
    <div class="auth-box">
      <div class="auth-logo">
        <div class="logo-mark" style="gap:.6rem;">
          <svg width="40" height="40" viewBox="0 0 32 32" fill="none" style="flex-shrink:0;">
          <defs>
            <linearGradient id="alg1" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#7c5cfc"/><stop offset="100%" stop-color="#a286ff"/>
            </linearGradient>
            <linearGradient id="alg2" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#a286ff" stop-opacity="0.85"/><stop offset="100%" stop-color="#c4aaff" stop-opacity="0.3"/>
            </linearGradient>
          </defs>
          <rect x="5" y="15" width="22" height="13" rx="3.5" fill="url(#alg1)" opacity="0.12" stroke="url(#alg1)" stroke-width="1.2"/>
          <path d="M5 18 Q9.5 13 16 16 Q22.5 19 27 14" stroke="url(#alg1)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
          <path d="M8 15 C8 12 9.5 10 8.5 7.5" stroke="url(#alg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
          <path d="M16 16 C16 13 17.5 11 16.5 8.5" stroke="url(#alg2)" stroke-width="1.4" fill="none" stroke-linecap="round"/>
          <path d="M24 14 C24 11 25.5 9 24.5 6.5" stroke="url(#alg2)" stroke-width="1.3" fill="none" stroke-linecap="round"/>
          <circle cx="16" cy="16" r="2" fill="url(#alg1)" opacity="0.95"/>
        </svg>
          OVER<span class="accent">FLOW</span>
        </div>
      </div>

      <p class="auth-eyebrow">Get started</p>
      <h2 class="auth-title">Create Account</h2>
      <p class="auth-subtitle">Start organizing your tasks and projects today</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control"
            placeholder="Choose a username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control"
            placeholder="you@example.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control"
            placeholder="At least 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="password2" class="form-control"
            placeholder="Repeat your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:.75rem;letter-spacing:.08em;">
          CREATE ACCOUNT →
        </button>
      </form>

      <div class="auth-divider">
        Already have an account? <a href="login.php">Sign in</a>
      </div>
    </div>
  </div>

</body>
</html>

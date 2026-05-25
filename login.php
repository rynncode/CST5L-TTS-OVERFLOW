<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            header('Location: transition.php');
            exit();
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Overflow</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

  <!-- Geometric Background -->
  <div class="geo-canvas">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      <line class="geo-line" x1="-40" y1="200" x2="700" y2="900" style="animation-delay:.1s"/>
      <line class="geo-line" x1="1480" y1="100" x2="600" y2="920" style="animation-delay:.3s"/>
      <line class="geo-line" x1="0" y1="0" x2="1440" y2="540" style="animation-delay:.5s;stroke:rgba(162,134,255,0.07);"/>
      <circle class="geo-circle" cx="200" cy="900" r="420" style="animation-delay:.2s"/>
      <circle class="geo-circle" cx="1300" cy="0" r="340" style="animation-delay:.6s"/>
      <circle class="geo-circle" cx="720" cy="450" r="260" style="animation-delay:.8s;stroke:rgba(162,134,255,0.06);"/>
      <line class="geo-line-2" x1="80" y1="74" x2="96" y2="74" style="animation-delay:1.5s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="88" y1="66" x2="88" y2="82" style="animation-delay:1.5s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1360" y1="800" x2="1376" y2="800" style="animation-delay:1.8s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1368" y1="792" x2="1368" y2="808" style="animation-delay:1.8s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <circle class="geo-dot" cx="200" cy="480" r="2.5" style="animation-delay:2.2s"/>
      <circle class="geo-dot" cx="1240" cy="300" r="2" style="animation-delay:2.4s;fill:rgba(162,134,255,0.7);"/>
      <circle class="geo-dot" cx="420" cy="100" r="1.5" style="animation-delay:2.6s"/>
      <path class="geo-line-2" d="M 30 30 L 30 80 M 30 30 L 80 30" style="animation-delay:1s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <path class="geo-line-2" d="M 1410 870 L 1410 820 M 1410 870 L 1360 870" style="animation-delay:1.2s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
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

      <p class="auth-eyebrow">Welcome back</p>
      <h2 class="auth-title">Sign In</h2>
      <p class="auth-subtitle">Access your workspace and pick up where you left off</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">✓ Account created! You can now sign in.</div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label class="form-label">Username or Email</label>
          <input type="text" name="identifier" class="form-control"
            placeholder="Enter your username or email"
            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
            required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control"
            placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:.75rem;letter-spacing:.08em;">
          SIGN IN →
        </button>
      </form>

      <div class="auth-divider">
        Don't have an account? <a href="register.php">Create one</a>
      </div>
    </div>
  </div>

</body>
</html>

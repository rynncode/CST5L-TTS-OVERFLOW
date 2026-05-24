<?php
// login.php
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
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

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
      <!-- Long diagonal lines -->
      <line class="geo-line" x1="-40" y1="200" x2="700" y2="900" style="animation-delay:.1s"/>
      <line class="geo-line" x1="1480" y1="100" x2="600" y2="920" style="animation-delay:.3s"/>
      <line class="geo-line" x1="0" y1="0" x2="1440" y2="540" style="animation-delay:.5s;stroke:rgba(162,134,255,0.07);"/>
      <!-- Arcs -->
      <circle class="geo-circle" cx="200" cy="900" r="420" style="animation-delay:.2s"/>
      <circle class="geo-circle" cx="1300" cy="0" r="340" style="animation-delay:.6s"/>
      <circle class="geo-circle" cx="720" cy="450" r="260" style="animation-delay:.8s;stroke:rgba(162,134,255,0.06);"/>
      <!-- Cross marks -->
      <line class="geo-line-2" x1="80" y1="74" x2="96" y2="74" style="animation-delay:1.5s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="88" y1="66" x2="88" y2="82" style="animation-delay:1.5s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1360" y1="800" x2="1376" y2="800" style="animation-delay:1.8s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1368" y1="792" x2="1368" y2="808" style="animation-delay:1.8s;stroke:rgba(162,134,255,0.4);stroke-width:1.5;"/>
      <!-- Dots -->
      <circle class="geo-dot" cx="200" cy="480" r="2.5" style="animation-delay:2.2s"/>
      <circle class="geo-dot" cx="1240" cy="300" r="2" style="animation-delay:2.4s;fill:rgba(162,134,255,0.7);"/>
      <circle class="geo-dot" cx="420" cy="100" r="1.5" style="animation-delay:2.6s"/>
      <!-- Corner bracket top-left -->
      <path class="geo-line-2" d="M 30 30 L 30 80 M 30 30 L 80 30" style="animation-delay:1s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <!-- Corner bracket bottom-right -->
      <path class="geo-line-2" d="M 1410 870 L 1410 820 M 1410 870 L 1360 870" style="animation-delay:1.2s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
    </svg>
  </div>

  <div class="auth-wrap">
    <div class="auth-box">
      <?php require_once 'includes/auth_logo.php'; ?>

</body>
</html>

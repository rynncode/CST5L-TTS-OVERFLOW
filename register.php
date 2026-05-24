<?php
// register.php
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
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username or email is already registered.';
            $stmt->close();
        } else {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $username, $email, $hashed);

            if ($ins->execute()) {
                $ins->close();
                $conn->close();
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
                $ins->close();
            }
        }
        $conn->close();
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

  <!-- Geometric Background(so ignore this) -->
  <div class="geo-canvas">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
      <!-- Diagonal lines -->
      <line class="geo-line" x1="0" y1="600" x2="900" y2="0" style="animation-delay:.1s"/>
      <line class="geo-line" x1="1440" y1="300" x2="400" y2="900" style="animation-delay:.35s"/>
      <line class="geo-line" x1="200" y1="0" x2="1440" y2="700" style="animation-delay:.55s;stroke:rgba(162,134,255,0.07);"/>
      <!-- Circles -->
      <circle class="geo-circle" cx="100" cy="100"  r="380" style="animation-delay:.2s"/>
      <circle class="geo-circle" cx="1340" cy="800" r="300" style="animation-delay:.5s"/>
      <circle class="geo-circle" cx="720"  cy="450" r="200" style="animation-delay:.9s;stroke:rgba(162,134,255,0.05);"/>
      <!-- Small decorative squares (drawn as paths) -->
      <rect class="geo-circle" x="60" y="760" width="28" height="28" rx="4"
            style="animation-delay:1.1s;stroke:rgba(162,134,255,0.35);stroke-dasharray:120;"/>
      <rect class="geo-circle" x="1350" y="60" width="20" height="20" rx="3"
            style="animation-delay:1.3s;stroke:rgba(162,134,255,0.35);stroke-dasharray:80;"/>
      <!-- Crosshairs -->
      <line class="geo-line-2" x1="1380" y1="450" x2="1400" y2="450" style="animation-delay:1.6s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="1390" y1="440" x2="1390" y2="460" style="animation-delay:1.6s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="50"   y1="440" x2="70"   y2="440" style="animation-delay:1.9s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <line class="geo-line-2" x1="60"   y1="430" x2="60"   y2="450" style="animation-delay:1.9s;stroke:rgba(162,134,255,0.45);stroke-width:1.5;"/>
      <!-- Corner brackets -->
      <path class="geo-line-2" d="M 30 30 L 30 80 M 30 30 L 80 30" style="animation-delay:1s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <path class="geo-line-2" d="M 1410 870 L 1410 820 M 1410 870 L 1360 870" style="animation-delay:1.2s;stroke:rgba(162,134,255,0.3);stroke-dasharray:100;"/>
      <!-- Dots -->
      <circle class="geo-dot" cx="900"  cy="200" r="2.5" style="animation-delay:2.1s"/>
      <circle class="geo-dot" cx="300"  cy="700" r="2"   style="animation-delay:2.3s;fill:rgba(162,134,255,0.7);"/>
      <circle class="geo-dot" cx="1100" cy="500" r="1.5" style="animation-delay:2.5s"/>
    </svg>
  </div>

  <div class="auth-wrap">
    <div class="auth-box">
      <?php require_once 'includes/auth_logo.php'; ?>

</body>
</html>

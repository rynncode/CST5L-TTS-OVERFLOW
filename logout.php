<?php
session_start();
session_unset();
session_destroy();

// AJAX call from animation — just die silently
if (isset($_GET['ajax'])) {
    http_response_code(200);
    exit();
}

header('Location: login.php?logout=1');
exit();

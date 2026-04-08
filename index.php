<?php
require_once 'config/db.php';
startSession();
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'resident';
    $dest = match($role) {
        'admin' => BASE_URL . '/admin/index.php',
        'staff' => BASE_URL . '/staff/index.php',
        default => BASE_URL . '/user/index.php',
    };
    header("Location: $dest");
} else {
    header("Location: " . BASE_URL . "/login.php");
}
exit;

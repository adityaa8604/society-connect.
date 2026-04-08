<?php
require_once 'config/db.php';
startSession();
if (isLoggedIn()) {
    logActivity((int)$_SESSION['user_id'], 'User logged out', 'auth');
}
session_unset();
session_destroy();
header("Location: " . BASE_URL . "/login.php");
exit;

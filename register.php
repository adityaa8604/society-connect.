<?php
/**
 * register.php — New User Registration
 */
require_once 'config/db.php';
startSession();

if (isLoggedIn()) { header("Location: " . BASE_URL . "/index.php"); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role     = trim($_POST['role']     ?? 'resident');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'This email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,?)");
            $ins->execute([$name, $email, $phone, $hash, $role]);
            $success = 'Account created successfully! You can now sign in.';
            logActivity((int)$db->lastInsertId(), 'New account registered', 'auth');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon"><i class="fa-solid fa-city fa-2x"></i></div>
            <h1><?= APP_NAME ?></h1>
            <p>Create your account and join your society's digital community today.</p>
            <div class="auth-feature-list mt-4">
                <div class="auth-feature-item"><i class="fa-solid fa-shield-halved"></i> Secure &amp; Private</div>
                <div class="auth-feature-item"><i class="fa-solid fa-mobile-screen"></i> Mobile Friendly</div>
                <div class="auth-feature-item"><i class="fa-solid fa-bell"></i> Real-time Updates</div>
                <div class="auth-feature-item"><i class="fa-solid fa-headset"></i> 24/7 Support</div>
            </div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h2>Create Account 🏢</h2>
            <p class="auth-subtitle">Join <?= APP_NAME ?> — it only takes a minute</p>

            <?php if ($error):   ?><div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= e($success) ?> <a href="<?= BASE_URL ?>/login.php" class="fw-bold">Sign In →</a></div><?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Your full name"
                               value="<?= e($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com"
                               value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="+91 9800000000"
                               value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select name="role" class="form-select">
                            <option value="resident" <?= ($_POST['role'] ?? '') === 'resident' ? 'selected' : '' ?>>Resident</option>
                            <option value="staff"    <?= ($_POST['role'] ?? '') === 'staff'    ? 'selected' : '' ?>>Staff</option>
                            <option value="admin"    <?= ($_POST['role'] ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required minlength="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fa-solid fa-user-plus me-2"></i> Create Account
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <span class="text-muted small">Already have an account?</span>
                <a href="<?= BASE_URL ?>/login.php" class="ms-1 small fw-700" style="color:var(--primary);">Sign In →</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

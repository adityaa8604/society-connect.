<?php
/**
 * login.php — Society Connect Login
 */
require_once 'config/db.php';
startSession();

// Already logged in?
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'resident';
    header("Location: " . BASE_URL . "/" . ($role === 'admin' ? 'admin' : ($role === 'staff' ? 'staff' : 'user')) . "/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $upd = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $upd->execute([$user['id']]);

            // Set session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            logActivity($user['id'], 'User logged in', 'auth');

            // Redirect based on role
            $dest = match($user['role']) {
                'admin' => BASE_URL . '/admin/index.php',
                'staff' => BASE_URL . '/staff/index.php',
                default => BASE_URL . '/user/index.php',
            };
            header("Location: $dest");
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">
    <!-- Left Panel -->
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon">
                <i class="fa-solid fa-city fa-2x"></i>
            </div>
            <h1><?= APP_NAME ?></h1>
            <p>Your complete digital solution for modern society management — residents, billing, complaints &amp; more.</p>

            <div class="auth-feature-list mt-4">
                <?php
                $features = [
                    ['icon' => 'users',            'text' => 'Resident & Flat Management'],
                    ['icon' => 'file-invoice-dollar','text'=> 'Automated Billing System'],
                    ['icon' => 'triangle-exclamation','text'=>'Smart Complaint Tracking'],
                    ['icon' => 'calendar-check',   'text' => 'Facility Booking System'],
                    ['icon' => 'door-open',        'text' => 'Visitor Log & Security'],
                    ['icon' => 'bullhorn',         'text' => 'Notice Board & Documents'],
                ];
                foreach ($features as $f): ?>
                <div class="auth-feature-item">
                    <i class="fa-solid fa-<?= $f['icon'] ?>"></i>
                    <?= $f['text'] ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel (Form) -->
    <div class="auth-right">
        <div class="auth-card">
            <h2>Welcome Back 👋</h2>
            <p class="auth-subtitle">Sign in to access your society connect portal</p>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                <i class="fa-solid fa-circle-xmark"></i> <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="your@email.com"
                               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control"
                               placeholder="Enter your password" required id="passwordInput">
                        <button type="button" class="btn btn-outline-secondary border-start-0"
                                onclick="togglePassword()"
                                style="border:1.5px solid #E2E8F0;border-left:none;border-radius:0 10px 10px 0;">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom mb-3">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Dashboard
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="text-muted small">Don't have an account?</span>
                <a href="<?= BASE_URL ?>/register.php" class="ms-1 small fw-700" style="color:var(--primary);">Register Here</a>
            </div>

            <!-- Demo Credentials Box -->
            <div class="mt-4 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                <p class="fw-700 mb-2" style="font-size:12px;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">
                    <i class="fa-solid fa-key me-1"></i> Demo Credentials (Password: password123)
                </p>
                <div class="row g-2">
                    <?php
                    $demos = [
                        ['Admin',    'admin@society.com',  'danger'],
                        ['Resident', 'rajesh@society.com', 'primary'],
                        ['Staff',    'ravi@society.com',   'success'],
                    ];
                    foreach ($demos as [$role, $email, $color]): ?>
                    <div class="col-4">
                        <button type="button"
                                onclick="fillDemo('<?= $email ?>')"
                                class="btn btn-sm w-100 py-1"
                                style="background:rgba(<?= $color === 'danger' ? '239,68,68' : ($color === 'primary' ? '79,70,229' : '16,185,129') ?>,0.1);
                                       color:var(--<?= $color ?>);font-size:11px;font-weight:700;border-radius:7px;">
                            <?= $role ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const inp  = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}
function fillDemo(email) {
    document.querySelector('input[name="email"]').value    = email;
    document.querySelector('input[name="password"]').value = 'password123';
    document.querySelector('input[name="password"]').focus();
}
</script>
</body>
</html>

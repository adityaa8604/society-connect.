<?php
/**
 * config/db.php
 * Central configuration: database, constants, helper functions
 */

// ============================================================
// APP CONFIGURATION
// ============================================================
define('APP_NAME',    'Society Connect');
define('APP_VERSION', '2.0');
define('BASE_URL',    '/society-connect'); // Change if folder name differs

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'society_connect');
define('DB_USER', 'root');
define('DB_PASS', '');          // Set your MySQL password if any

// ============================================================
// PDO CONNECTION (Singleton)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(renderDBError($e->getMessage()));
        }
    }
    return $pdo;
}

function renderDBError(string $msg): string {
    return '<!DOCTYPE html><html><head><title>DB Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
    <body class="bg-danger-subtle d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow p-4 text-center" style="max-width:500px">
      <h4 class="text-danger">⚠ Database Connection Failed</h4>
      <p class="text-muted small">' . htmlspecialchars($msg) . '</p>
      <hr><p class="small">Check: MySQL running | DB name: <strong>society_connect</strong> | config/db.php credentials</p>
    </div></body></html>';
}

// ============================================================
// SESSION HELPER
// ============================================================
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(string $role = ''): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        // Redirect to their own dashboard
        $dashMap = [
            'admin'    => BASE_URL . '/admin/index.php',
            'resident' => BASE_URL . '/user/index.php',
            'staff'    => BASE_URL . '/staff/index.php',
        ];
        header('Location: ' . ($dashMap[$_SESSION['user_role']] ?? BASE_URL . '/login.php'));
        exit;
    }
}

function requireAnyRole(array $roles): void {
    startSession();
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function currentUser(): array {
    startSession();
    return [
        'id'   => $_SESSION['user_id']   ?? 0,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
        'email'=> $_SESSION['user_email']?? '',
    ];
}

// ============================================================
// FLASH MESSAGES
// ============================================================
function setFlash(string $type, string $msg): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): string {
    startSession();
    if (!isset($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cls = match($f['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    $icon = match($f['type']) {
        'success' => 'circle-check',
        'error'   => 'circle-xmark',
        'warning' => 'triangle-exclamation',
        default   => 'circle-info',
    };
    return '<div class="alert ' . $cls . ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid fa-' . $icon . '"></i>
        <span>' . htmlspecialchars($f['msg']) . '</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function monthName(int $m): string {
    return date('F', mktime(0, 0, 0, $m, 1));
}

function statusBadge(string $status, string $type = 'general'): string {
    $map = [
        // Bill statuses
        'paid'        => 'success',
        'pending'     => 'warning',
        'overdue'     => 'danger',
        'waived'      => 'secondary',
        // Complaint statuses
        'open'        => 'danger',
        'assigned'    => 'warning',
        'in_progress' => 'info',
        'resolved'    => 'success',
        'closed'      => 'secondary',
        // Booking statuses
        'approved'    => 'success',
        'rejected'    => 'danger',
        'cancelled'   => 'secondary',
        'completed'   => 'primary',
        // Visitor statuses
        'inside'      => 'success',
        'exited'      => 'secondary',
        'pre_approved'=> 'info',
        // Misc
        'active'      => 'success',
        'inactive'    => 'secondary',
        'available'   => 'success',
        'maintenance' => 'warning',
        'closed'      => 'danger',
        'occupied'    => 'primary',
        'vacant'      => 'success',
        'under_maintenance' => 'warning',
        // Priority
        'low'         => 'secondary',
        'medium'      => 'info',
        'high'        => 'warning',
        'critical'    => 'danger',
    ];
    $cls   = $map[$status] ?? 'secondary';
    $label = ucfirst(str_replace('_', ' ', $status));
    return "<span class=\"badge bg-{$cls}\">{$label}</span>";
}

function categoryIcon(string $cat): string {
    $icons = [
        'electrical'  => 'bolt',
        'plumbing'    => 'droplet',
        'civil'       => 'hammer',
        'housekeeping'=> 'broom',
        'security'    => 'shield-halved',
        'lift'        => 'elevator',
        'common_area' => 'people-group',
        'other'       => 'circle-dot',
        'general'     => 'bullhorn',
        'urgent'      => 'triangle-exclamation',
        'event'       => 'calendar-star',
        'finance'     => 'indian-rupee-sign',
    ];
    return $icons[$cat] ?? 'circle-dot';
}

function logActivity(int $userId, string $action, string $module = ''): void {
    try {
        $db   = getDB();
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, module, ip_address) VALUES (?,?,?,?)");
        $stmt->execute([$userId, $action, $module, $ip]);
    } catch (Exception $e) {
        // Silently fail — activity log shouldn't break the app
    }
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60)   . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600)  . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400)  . ' days ago';
    return date('d M Y', $time);
}

function formatCurrency(float $amount): string {
    return '₹' . number_format($amount, 2);
}

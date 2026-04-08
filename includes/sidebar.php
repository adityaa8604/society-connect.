<?php
/**
 * includes/sidebar.php
 * Unified sidebar - shows menu items based on user role
 * Requires: $activePage to be set, session started, BASE_URL defined
 */

$user       = currentUser();
$userRole   = $user['role'];
$userName   = $user['name'];
$userInitial= strtoupper(substr($userName, 0, 1));

// Pending counts for badges (admin/staff)
$db           = getDB();
$pendingComplaints = 0;
$pendingBookings   = 0;
$activeVisitors    = 0;
try {
    if ($userRole === 'admin') {
        $pendingComplaints = $db->query("SELECT COUNT(*) FROM complaints WHERE status='open'")->fetchColumn();
        $pendingBookings   = $db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
        $activeVisitors    = $db->query("SELECT COUNT(*) FROM visitors WHERE status='inside'")->fetchColumn();
    } elseif ($userRole === 'staff') {
        $staffRow = $db->prepare("SELECT id FROM staff WHERE user_id=? LIMIT 1");
        $staffRow->execute([$user['id']]);
        $staffId = $staffRow->fetchColumn();
        if ($staffId) {
            $pendingComplaints = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status IN('assigned','in_progress')");
            $pendingComplaints->execute([$staffId]);
            $pendingComplaints = $pendingComplaints->fetchColumn();
        }
    }
} catch (Exception $e) {}

// Nav items per role
$adminNav = [
    'main' => [
        ['href' => BASE_URL . '/admin/index.php',     'icon' => 'gauge-high',       'label' => 'Dashboard',    'page' => 'dashboard'],
    ],
    'management' => [
        ['href' => BASE_URL . '/admin/residents.php', 'icon' => 'users',            'label' => 'Residents',    'page' => 'residents'],
        ['href' => BASE_URL . '/admin/staff.php',     'icon' => 'user-tie',         'label' => 'Staff',        'page' => 'staff'],
        ['href' => BASE_URL . '/admin/vendors.php',   'icon' => 'handshake',        'label' => 'Vendors',      'page' => 'vendors'],
        ['href' => BASE_URL . '/admin/flats.php',     'icon' => 'building',         'label' => 'Flats',        'page' => 'flats'],
    ],
    'operations' => [
        ['href' => BASE_URL . '/admin/billing.php',   'icon' => 'file-invoice-dollar','label' => 'Billing',    'page' => 'billing'],
        ['href' => BASE_URL . '/admin/complaints.php','icon' => 'triangle-exclamation','label' => 'Complaints', 'page' => 'complaints', 'badge' => $pendingComplaints],
        ['href' => BASE_URL . '/admin/visitors.php',  'icon' => 'door-open',        'label' => 'Visitors',     'page' => 'visitors', 'badge' => $activeVisitors],
        ['href' => BASE_URL . '/admin/bookings.php',  'icon' => 'calendar-check',   'label' => 'Bookings',     'page' => 'bookings', 'badge' => $pendingBookings],
        ['href' => BASE_URL . '/admin/notices.php',   'icon' => 'bullhorn',         'label' => 'Notices',      'page' => 'notices'],
    ],
];

$userNav = [
    'main' => [
        ['href' => BASE_URL . '/user/index.php',      'icon' => 'gauge-high',       'label' => 'Dashboard',    'page' => 'dashboard'],
        ['href' => BASE_URL . '/user/profile.php',    'icon' => 'user-circle',      'label' => 'My Profile',   'page' => 'profile'],
    ],
    'services' => [
        ['href' => BASE_URL . '/user/billing.php',    'icon' => 'file-invoice-dollar','label' => 'My Bills',   'page' => 'billing'],
        ['href' => BASE_URL . '/user/complaints.php', 'icon' => 'triangle-exclamation','label' => 'Complaints','page' => 'complaints'],
        ['href' => BASE_URL . '/user/bookings.php',   'icon' => 'calendar-check',   'label' => 'Book Facility','page' => 'bookings'],
        ['href' => BASE_URL . '/user/visitors.php',   'icon' => 'door-open',        'label' => 'Visitors',     'page' => 'visitors'],
        ['href' => BASE_URL . '/user/notices.php',    'icon' => 'bullhorn',         'label' => 'Notices',      'page' => 'notices'],
    ],
];

$staffNav = [
    'main' => [
        ['href' => BASE_URL . '/staff/index.php',        'icon' => 'gauge-high',    'label' => 'Dashboard',    'page' => 'dashboard'],
        ['href' => BASE_URL . '/staff/complaints.php',   'icon' => 'triangle-exclamation','label'=>'My Tasks', 'page' => 'complaints', 'badge' => $pendingComplaints],
        ['href' => BASE_URL . '/staff/visitors.php',     'icon' => 'door-open',     'label' => 'Visitors',     'page' => 'visitors'],
    ],
];

$nav = match($userRole) {
    'admin'    => $adminNav,
    'staff'    => $staffNav,
    default    => $userNav,
};

function renderNavItem(array $item, string $activePage): string {
    $isActive = ($activePage === ($item['page'] ?? '')) ? 'active' : '';
    $badge    = !empty($item['badge']) && $item['badge'] > 0
        ? '<span class="nav-badge-count">' . $item['badge'] . '</span>'
        : '';
    return '
    <a href="' . e($item['href']) . '" class="nav-link-custom ' . $isActive . '">
        <span class="nav-icon"><i class="fa-solid fa-' . e($item['icon']) . '"></i></span>
        <span>' . e($item['label']) . '</span>
        ' . $badge . '
    </a>';
}
?>

<aside class="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-wrapper">
            <div class="brand-icon-sidebar">
                <i class="fa-solid fa-city"></i>
            </div>
            <div>
                <div class="brand-name">Society Connect</div>
                <div class="brand-sub">Management System</div>
            </div>
        </div>
    </div>

    <!-- User Card -->
    <div class="sidebar-user-card">
        <div class="user-avatar-sm"><?= $userInitial ?></div>
        <div class="user-info-sm">
            <div class="user-name-sm"><?= e($userName) ?></div>
            <div class="user-role-sm"><?= ucfirst($userRole) ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php foreach ($nav as $section => $items): ?>
            <div class="nav-section-label"><?= ucfirst($section) ?></div>
            <?php foreach ($items as $item): ?>
                <?= renderNavItem($item, $activePage ?? '') ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout-sidebar"
           data-confirm="Are you sure you want to logout?">
            <i class="fa-solid fa-right-from-bracket fa-fw"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

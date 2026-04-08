<?php
/**
 * admin/index.php — Admin Dashboard
 */
require_once '../config/db.php';
requireLogin('admin');

$db = getDB();

// ---- Statistics ----
$totalResidents   = $db->query("SELECT COUNT(*) FROM residents")->fetchColumn();
$totalFlats       = $db->query("SELECT COUNT(*) FROM flats")->fetchColumn();
$occupiedFlats    = $db->query("SELECT COUNT(*) FROM flats WHERE status='occupied'")->fetchColumn();
$totalStaff       = $db->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$totalVendors     = $db->query("SELECT COUNT(*) FROM vendors WHERE status='active'")->fetchColumn();

// Billing
$totalBilled    = $db->query("SELECT COALESCE(SUM(amount),0) FROM billing WHERE year=YEAR(NOW()) AND month=MONTH(NOW())")->fetchColumn();
$collected      = $db->query("SELECT COALESCE(SUM(amount),0) FROM billing WHERE status='paid' AND year=YEAR(NOW()) AND month=MONTH(NOW())")->fetchColumn();
$pendingAmount  = $db->query("SELECT COALESCE(SUM(amount+penalty),0) FROM billing WHERE status IN('pending','overdue')")->fetchColumn();
$overdueCount   = $db->query("SELECT COUNT(*) FROM billing WHERE status='overdue'")->fetchColumn();

// Complaints
$openComplaints     = $db->query("SELECT COUNT(*) FROM complaints WHERE status='open'")->fetchColumn();
$resolvedThisMonth  = $db->query("SELECT COUNT(*) FROM complaints WHERE status='resolved' AND MONTH(resolved_at)=MONTH(NOW()) AND YEAR(resolved_at)=YEAR(NOW())")->fetchColumn();

// Visitors
$activeVisitors = $db->query("SELECT COUNT(*) FROM visitors WHERE status='inside'")->fetchColumn();
$todayVisitors  = $db->query("SELECT COUNT(*) FROM visitors WHERE DATE(entry_time)=CURDATE()")->fetchColumn();

// Pending bookings
$pendingBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();

// Recent activities
$recentComplaints = $db->query("
    SELECT c.*, f.flat_no, u.name as resident_name
    FROM complaints c
    JOIN residents r ON c.resident_id = r.id
    JOIN users u ON r.user_id = u.id
    JOIN flats f ON c.flat_id = f.id
    ORDER BY c.created_at DESC LIMIT 5")->fetchAll();

$recentBills = $db->query("
    SELECT b.*, f.flat_no, u.name as resident_name
    FROM billing b
    JOIN flats f ON b.flat_id = f.id
    LEFT JOIN residents r ON b.resident_id = r.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY b.created_at DESC LIMIT 5")->fetchAll();

$recentVisitors = $db->query("
    SELECT v.*, f.flat_no
    FROM visitors v
    JOIN flats f ON v.flat_id = f.id
    ORDER BY v.entry_time DESC LIMIT 5")->fetchAll();

$pendingBookingsList = $db->query("
    SELECT bk.*, f.name as facility_name, fl.flat_no, u.name as resident_name
    FROM bookings bk
    JOIN facilities f ON bk.facility_id = f.id
    JOIN flats fl ON bk.flat_id = fl.id
    JOIN residents r ON bk.resident_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE bk.status='pending'
    ORDER BY bk.created_at DESC LIMIT 4")->fetchAll();

$recentNotices = $db->query("
    SELECT n.*, u.name as posted_by_name
    FROM notices n JOIN users u ON n.posted_by = u.id
    WHERE n.is_active=1 ORDER BY n.created_at DESC LIMIT 3")->fetchAll();

$pageTitle   = 'Admin Dashboard';
$pageSubtitle= 'Overview of society operations';
$activePage  = 'dashboard';
require_once '../includes/header.php';
?>

<!-- STATS ROW 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-users"></i></div>
            <div class="stat-num" data-target="<?= $totalResidents ?>"><?= $totalResidents ?></div>
            <div class="stat-label">Total Residents</div>
            <div class="mini-progress mt-2"><div class="mini-progress-bar" style="width:<?= $totalFlats > 0 ? round($occupiedFlats/$totalFlats*100) : 0 ?>%"></div></div>
            <small class="text-muted" style="font-size:11px;"><?= $occupiedFlats ?>/<?= $totalFlats ?> flats occupied</small>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div class="stat-num" data-target="<?= (int)$collected ?>" data-prefix="₹"><?= formatCurrency($collected) ?></div>
            <div class="stat-label">Collected This Month</div>
            <div class="stat-trend up mt-1">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <?= $totalBilled > 0 ? round($collected/$totalBilled*100) : 0 ?>% collection rate
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card danger">
            <div class="stat-icon-wrap danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-num" data-target="<?= $openComplaints ?>"><?= $openComplaints ?></div>
            <div class="stat-label">Open Complaints</div>
            <div class="stat-trend up mt-1">
                <i class="fa-solid fa-circle-check"></i>
                <?= $resolvedThisMonth ?> resolved this month
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-num" data-target="<?= $activeVisitors ?>"><?= $activeVisitors ?></div>
            <div class="stat-label">Active Visitors</div>
            <div class="stat-trend mt-1" style="color:var(--secondary);">
                <i class="fa-solid fa-calendar-day"></i>
                <?= $todayVisitors ?> visitors today
            </div>
        </div>
    </div>
</div>

<!-- STATS ROW 2 -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="stat-num"><?= formatCurrency($pendingAmount) ?></div>
            <div class="stat-label">Total Dues Pending</div>
            <small class="text-danger fw-700" style="font-size:11px;"><?= $overdueCount ?> overdue bills</small>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-num" data-target="<?= $pendingBookings ?>"><?= $pendingBookings ?></div>
            <div class="stat-label">Pending Bookings</div>
            <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-sc primary xs mt-2">Review Now</a>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-user-tie"></i></div>
            <div class="stat-num" data-target="<?= $totalStaff ?>"><?= $totalStaff ?></div>
            <div class="stat-label">Active Staff Members</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-handshake"></i></div>
            <div class="stat-num" data-target="<?= $totalVendors ?>"><?= $totalVendors ?></div>
            <div class="stat-label">Active Vendors</div>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="sc-card mb-4">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
    </div>
    <div class="sc-card-body">
        <div class="row g-3">
            <?php
            $quickActions = [
                ['href' => BASE_URL.'/admin/residents.php?action=add', 'icon'=>'user-plus',   'label'=>'Add Resident',  'color'=>'primary'],
                ['href' => BASE_URL.'/admin/billing.php?action=add',   'icon'=>'plus-circle', 'label'=>'Add Bill',      'color'=>'success'],
                ['href' => BASE_URL.'/admin/visitors.php?action=add',  'icon'=>'user-check',  'label'=>'Log Visitor',   'color'=>'secondary'],
                ['href' => BASE_URL.'/admin/notices.php?action=add',   'icon'=>'bullhorn',    'label'=>'Post Notice',   'color'=>'warning'],
                ['href' => BASE_URL.'/admin/complaints.php',           'icon'=>'list-check',  'label'=>'View Complaints','color'=>'danger'],
                ['href' => BASE_URL.'/admin/bookings.php',             'icon'=>'calendar-check','label'=>'Bookings',    'color'=>'primary'],
            ];
            foreach ($quickActions as $qa): ?>
            <div class="col-lg-2 col-md-4 col-6">
                <a href="<?= $qa['href'] ?>" class="quick-action-card text-decoration-none">
                    <div class="qa-icon <?= $qa['color'] ?>">
                        <i class="fa-solid fa-<?= $qa['icon'] ?>"></i>
                    </div>
                    <div class="qa-label"><?= $qa['label'] ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- BOTTOM ROW: Complaints + Billing + Visitors -->
<div class="row g-4">
    <!-- Recent Complaints -->
    <div class="col-lg-4">
        <div class="sc-card h-100">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-triangle-exclamation"></i> Recent Complaints</div>
                <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn-sc outline-primary xs">View All</a>
            </div>
            <div class="sc-card-body p-0">
                <?php if (empty($recentComplaints)): ?>
                    <div class="empty-state"><i class="fa-solid fa-circle-check empty-icon"></i><p>No complaints!</p></div>
                <?php else: ?>
                <div class="timeline p-3">
                    <?php foreach ($recentComplaints as $c): ?>
                    <div class="timeline-item">
                        <div class="timeline-time"><?= timeAgo($c['created_at']) ?></div>
                        <div class="timeline-text"><?= e(substr($c['title'], 0, 45)) ?><?= strlen($c['title']) > 45 ? '...' : '' ?></div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="flat-pill"><?= e($c['flat_no']) ?></span>
                            <?= statusBadge($c['status']) ?>
                            <?= statusBadge($c['priority']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Bills -->
    <div class="col-lg-4">
        <div class="sc-card h-100">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-file-invoice-dollar"></i> Recent Bills</div>
                <a href="<?= BASE_URL ?>/admin/billing.php" class="btn-sc outline-primary xs">View All</a>
            </div>
            <div class="sc-card-body p-0">
                <?php if (empty($recentBills)): ?>
                    <div class="empty-state"><p>No bills found.</p></div>
                <?php else: ?>
                <table class="table-sc">
                    <tbody>
                    <?php foreach ($recentBills as $b): ?>
                    <tr>
                        <td>
                            <div class="fw-700 small"><?= e($b['flat_no']) ?></div>
                            <small class="text-muted"><?= monthName($b['month']) ?> <?= $b['year'] ?></small>
                        </td>
                        <td class="text-end">
                            <div class="fw-700"><?= formatCurrency($b['amount']) ?></div>
                            <?= statusBadge($b['status']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Visitors + Pending Bookings -->
    <div class="col-lg-4">
        <!-- Active Visitors -->
        <div class="sc-card mb-3">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-door-open"></i> Today's Visitors</div>
                <a href="<?= BASE_URL ?>/admin/visitors.php" class="btn-sc outline-primary xs">View All</a>
            </div>
            <div class="sc-card-body p-0">
                <?php if (empty($recentVisitors)): ?>
                    <div class="empty-state py-3"><p>No visitors today.</p></div>
                <?php else: ?>
                <?php foreach ($recentVisitors as $v): ?>
                <div class="d-flex align-items-center gap-3 p-3 border-bottom" style="border-color:#F1F5F9!important;">
                    <div class="user-avatar-sm" style="width:34px;height:34px;font-size:13px;">
                        <?= strtoupper(substr($v['visitor_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-700 small"><?= e($v['visitor_name']) ?></div>
                        <div class="text-muted" style="font-size:11px;">→ Flat <?= e($v['flat_no']) ?></div>
                    </div>
                    <?= statusBadge($v['status']) ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Bookings -->
        <?php if (!empty($pendingBookingsList)): ?>
        <div class="sc-card">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-calendar-check"></i> Pending Bookings</div>
            </div>
            <div class="sc-card-body p-0">
                <?php foreach ($pendingBookingsList as $bk): ?>
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom" style="border-color:#F1F5F9!important;">
                    <div>
                        <div class="fw-700 small"><?= e($bk['facility_name']) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= e($bk['flat_no']) ?> · <?= date('d M', strtotime($bk['booking_date'])) ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/bookings.php?approve=<?= $bk['id'] ?>"
                       class="btn-sc success xs">Approve</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Notices -->
<?php if (!empty($recentNotices)): ?>
<div class="sc-card mt-4">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-bullhorn"></i> Recent Notices</div>
        <a href="<?= BASE_URL ?>/admin/notices.php" class="btn-sc outline-primary xs">Manage Notices</a>
    </div>
    <div class="sc-card-body">
        <div class="row g-3">
        <?php foreach ($recentNotices as $n): ?>
            <div class="col-md-4">
                <div class="notice-card <?= e($n['category']) ?>">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-<?= $n['category'] === 'urgent' ? 'danger' : ($n['category'] === 'event' ? 'warning' : 'primary') ?>">
                            <i class="fa-solid fa-<?= categoryIcon($n['category']) ?> me-1"></i>
                            <?= ucfirst($n['category']) ?>
                        </span>
                        <?php if ($n['is_pinned']): ?><span class="badge bg-secondary"><i class="fa-solid fa-thumbtack me-1"></i>Pinned</span><?php endif; ?>
                    </div>
                    <h5><?= e($n['title']) ?></h5>
                    <p><?= e(substr($n['content'], 0, 100)) ?>...</p>
                    <div class="notice-meta">
                        <span><i class="fa-regular fa-calendar"></i><?= date('d M Y', strtotime($n['created_at'])) ?></span>
                        <span><i class="fa-regular fa-eye"></i><?= $n['views'] ?> views</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

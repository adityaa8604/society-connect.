<?php
/**
 * user/index.php — Resident Dashboard
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

// Get resident info
$resStmt = $db->prepare("SELECT r.*, f.flat_no, f.type as flat_type, f.block
                          FROM residents r JOIN flats f ON r.flat_id=f.id
                          WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();

if (!$resident) {
    // User has no resident profile yet
    $pageTitle = 'Dashboard'; $activePage = 'dashboard';
    require_once '../includes/header.php';
    echo '<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-house-circle-exclamation"></i></div>
    <h5>No Resident Profile</h5><p>Your account hasn\'t been linked to a flat yet. Please contact the admin.</p></div>';
    require_once '../includes/footer.php';
    exit;
}

$resId  = $resident['id'];
$flatId = $resident['flat_id'];

// Bills
$pendingBills  = $db->prepare("SELECT COUNT(*) FROM billing WHERE flat_id=? AND status IN('pending','overdue')");
$pendingBills->execute([$flatId]);
$pendingBills = $pendingBills->fetchColumn();

$totalDue = $db->prepare("SELECT COALESCE(SUM(amount+penalty),0) FROM billing WHERE flat_id=? AND status IN('pending','overdue')");
$totalDue->execute([$flatId]);
$totalDue = $totalDue->fetchColumn();

// Complaints
$myComplaints = $db->prepare("SELECT COUNT(*) FROM complaints WHERE resident_id=?");
$myComplaints->execute([$resId]);
$myComplaints = $myComplaints->fetchColumn();

$openComplaints = $db->prepare("SELECT COUNT(*) FROM complaints WHERE resident_id=? AND status IN('open','assigned','in_progress')");
$openComplaints->execute([$resId]);
$openComplaints = $openComplaints->fetchColumn();

// Bookings
$myBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE resident_id=? AND status IN('pending','approved')");
$myBookings->execute([$resId]);
$myBookings = $myBookings->fetchColumn();

// Recent bills
$recentBills = $db->prepare("SELECT * FROM billing WHERE flat_id=? ORDER BY year DESC, month DESC LIMIT 4");
$recentBills->execute([$flatId]);
$recentBills = $recentBills->fetchAll();

// My recent complaints
$recentComplaints = $db->prepare("SELECT * FROM complaints WHERE resident_id=? ORDER BY created_at DESC LIMIT 4");
$recentComplaints->execute([$resId]);
$recentComplaints = $recentComplaints->fetchAll();

// Active notices
$notices = $db->query("SELECT n.*, u.name as posted_by_name FROM notices n JOIN users u ON n.posted_by=u.id
                       WHERE n.is_active=1 ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT 4")->fetchAll();

$pageTitle   = 'My Dashboard';
$pageSubtitle= 'Welcome back, ' . $user['name'] . '!';
$activePage  = 'dashboard';
require_once '../includes/header.php';
?>

<!-- Flat Info Banner -->
<div class="sc-card mb-4" style="background:linear-gradient(135deg,#4F46E5,#06B6D4);border:none;">
    <div class="sc-card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-4">
                <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:white;">
                    <i class="fa-solid fa-house"></i>
                </div>
                <div>
                    <h3 style="color:white;font-weight:800;margin:0;"><?= e($resident['flat_no']) ?></h3>
                    <p style="color:rgba(255,255,255,0.8);margin:0;"><?= e($resident['flat_type']) ?> · Block <?= e($resident['block']) ?></p>
                    <span style="background:rgba(255,255,255,0.2);color:white;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                        <?= ucfirst($resident['resident_type']) ?>
                    </span>
                </div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
                <div style="text-align:center;color:white;">
                    <div style="font-size:22px;font-weight:800;"><?= $resident['members_count'] ?></div>
                    <div style="font-size:12px;opacity:0.8;">Members</div>
                </div>
                <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
                <div style="text-align:center;color:white;">
                    <div style="font-size:22px;font-weight:800;"><?= $pendingBills ?></div>
                    <div style="font-size:12px;opacity:0.8;">Pending Bills</div>
                </div>
                <div style="width:1px;background:rgba(255,255,255,0.2);"></div>
                <div style="text-align:center;color:white;">
                    <div style="font-size:22px;font-weight:800;"><?= $openComplaints ?></div>
                    <div style="font-size:12px;opacity:0.8;">Open Issues</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card <?= $totalDue > 0 ? 'danger' : 'success' ?>">
            <div class="stat-icon-wrap <?= $totalDue > 0 ? 'danger' : 'success' ?>"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div class="stat-num"><?= formatCurrency($totalDue) ?></div>
            <div class="stat-label">Amount Due</div>
            <a href="<?= BASE_URL ?>/user/billing.php" class="btn-sc <?= $totalDue > 0 ? 'danger' : 'success' ?> xs mt-2">View Bills</a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-num"><?= $openComplaints ?></div>
            <div class="stat-label">Open Complaints</div>
            <a href="<?= BASE_URL ?>/user/complaints.php" class="btn-sc warning xs mt-2">View All</a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-num"><?= $myBookings ?></div>
            <div class="stat-label">My Bookings</div>
            <a href="<?= BASE_URL ?>/user/bookings.php" class="btn-sc primary xs mt-2">Book Facility</a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-bullhorn"></i></div>
            <div class="stat-num"><?= count($notices) ?></div>
            <div class="stat-label">Active Notices</div>
            <a href="<?= BASE_URL ?>/user/notices.php" class="btn-sc secondary xs mt-2">Read Notices</a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="sc-card mb-4">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
    </div>
    <div class="sc-card-body">
        <div class="row g-3">
            <?php $qas = [
                [BASE_URL.'/user/complaints.php?action=add','triangle-exclamation','Lodge Complaint','danger'],
                [BASE_URL.'/user/bookings.php?action=add',  'calendar-plus',       'Book Facility',  'primary'],
                [BASE_URL.'/user/billing.php',              'file-invoice-dollar', 'Pay Bills',      'success'],
                [BASE_URL.'/user/visitors.php?action=add',  'user-plus',           'Pre-approve Visitor','secondary'],
                [BASE_URL.'/user/notices.php',              'bullhorn',            'View Notices',   'warning'],
                [BASE_URL.'/user/profile.php',              'user-circle',         'My Profile',     'primary'],
            ];
            foreach ($qas as [$href,$icon,$label,$color]): ?>
            <div class="col-lg-2 col-md-4 col-6">
                <a href="<?= $href ?>" class="quick-action-card text-decoration-none">
                    <div class="qa-icon <?= $color ?>"><i class="fa-solid fa-<?= $icon ?>"></i></div>
                    <div class="qa-label"><?= $label ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bills + Complaints -->
<div class="row g-4">
    <!-- Recent Bills -->
    <div class="col-lg-6">
        <div class="sc-card h-100">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-receipt"></i> My Recent Bills</div>
                <a href="<?= BASE_URL ?>/user/billing.php" class="btn-sc outline-primary xs">View All</a>
            </div>
            <div class="table-responsive">
                <?php if (empty($recentBills)): ?>
                <div class="empty-state py-4"><p>No bills found.</p></div>
                <?php else: ?>
                <table class="table-sc">
                    <thead><tr><th>Bill Type</th><th>Month</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentBills as $b): ?>
                    <tr>
                        <td><?= e($b['bill_type']) ?></td>
                        <td><?= monthName($b['month']) ?> <?= $b['year'] ?></td>
                        <td class="fw-700"><?= formatCurrency($b['amount']) ?></td>
                        <td><?= statusBadge($b['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- My Complaints -->
    <div class="col-lg-6">
        <div class="sc-card h-100">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-triangle-exclamation"></i> My Complaints</div>
                <a href="<?= BASE_URL ?>/user/complaints.php" class="btn-sc outline-primary xs">View All</a>
            </div>
            <div class="sc-card-body p-0">
                <?php if (empty($recentComplaints)): ?>
                <div class="empty-state py-4"><p>No complaints lodged.</p></div>
                <?php else: ?>
                <?php foreach ($recentComplaints as $c): ?>
                <div class="d-flex align-items-start gap-3 p-3 border-bottom" style="border-color:#F1F5F9!important;">
                    <div class="mt-1" style="color:var(--primary);font-size:16px;">
                        <i class="fa-solid fa-<?= categoryIcon($c['category']) ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-700 small"><?= e($c['title']) ?></div>
                        <div class="d-flex gap-2 mt-1">
                            <?= statusBadge($c['status']) ?>
                            <?= statusBadge($c['priority']) ?>
                        </div>
                    </div>
                    <small class="text-muted"><?= timeAgo($c['created_at']) ?></small>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Notices -->
<?php if (!empty($notices)): ?>
<div class="sc-card mt-4">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-bullhorn"></i> Society Notices</div>
        <a href="<?= BASE_URL ?>/user/notices.php" class="btn-sc outline-primary xs">View All</a>
    </div>
    <div class="sc-card-body">
        <div class="row g-3">
        <?php foreach ($notices as $n): ?>
        <div class="col-md-6">
            <div class="notice-card <?= e($n['category']) ?>">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-<?= $n['category']==='urgent'?'danger':($n['category']==='event'?'warning':'primary') ?>">
                        <i class="fa-solid fa-<?= categoryIcon($n['category']) ?> me-1"></i>
                        <?= ucfirst($n['category']) ?>
                    </span>
                    <?php if ($n['is_pinned']): ?><span class="badge bg-secondary"><i class="fa-solid fa-thumbtack me-1"></i>Pinned</span><?php endif; ?>
                </div>
                <h5><?= e($n['title']) ?></h5>
                <p><?= e(substr($n['content'],0,100)) ?>...</p>
                <div class="notice-meta">
                    <span><i class="fa-regular fa-calendar"></i><?= date('d M Y', strtotime($n['created_at'])) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

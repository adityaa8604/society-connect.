<?php
/**
 * staff/index.php — Staff Dashboard
 */
require_once '../config/db.php';
requireLogin('staff');
$db   = getDB();
$user = currentUser();

// Get staff record
$staffStmt = $db->prepare("SELECT s.*, u.name, u.email, u.phone FROM staff s JOIN users u ON s.user_id=u.id WHERE s.user_id=? LIMIT 1");
$staffStmt->execute([$user['id']]);
$staffInfo = $staffStmt->fetch();

if (!$staffInfo) {
    $pageTitle = 'Dashboard'; $activePage = 'dashboard';
    require_once '../includes/header.php';
    echo '<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-user-tie"></i></div>
    <h5>Staff Profile Not Found</h5><p>Contact admin to set up your staff profile.</p></div>';
    require_once '../includes/footer.php';
    exit;
}

$staffId = $staffInfo['id'];

// Stats
$myAssigned    = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status='assigned'");       $myAssigned->execute([$staffId]);    $myAssigned    = $myAssigned->fetchColumn();
$myInProgress  = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status='in_progress'");   $myInProgress->execute([$staffId]);  $myInProgress  = $myInProgress->fetchColumn();
$myResolved    = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status='resolved'");      $myResolved->execute([$staffId]);    $myResolved    = $myResolved->fetchColumn();
$myTotalTasks  = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=?");                            $myTotalTasks->execute([$staffId]);  $myTotalTasks  = $myTotalTasks->fetchColumn();

// Open complaints (not assigned yet — for awareness)
$openCount = $db->query("SELECT COUNT(*) FROM complaints WHERE status='open'")->fetchColumn();

// My pending tasks
$pendingTasks = $db->prepare("SELECT c.*, f.flat_no, u.name as resident_name
                               FROM complaints c
                               JOIN residents r ON c.resident_id=r.id
                               JOIN users u ON r.user_id=u.id
                               JOIN flats f ON c.flat_id=f.id
                               WHERE c.assigned_to=? AND c.status IN('assigned','in_progress')
                               ORDER BY FIELD(c.priority,'critical','high','medium','low'), c.created_at DESC");
$pendingTasks->execute([$staffId]);
$pendingTasks = $pendingTasks->fetchAll();

$pageTitle   = 'Staff Dashboard';
$pageSubtitle= 'Welcome, ' . $user['name'];
$activePage  = 'dashboard';
require_once '../includes/header.php';
?>

<!-- Staff Info Banner -->
<div class="sc-card mb-4" style="background:linear-gradient(135deg,#0891B2,#06B6D4);border:none;">
    <div class="sc-card-body">
        <div class="d-flex align-items-center gap-4">
            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:white;">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <h3 style="color:white;font-weight:800;margin:0;"><?= e($staffInfo['name']) ?></h3>
                <p style="color:rgba(255,255,255,0.8);margin:2px 0 8px;"><?= e($staffInfo['designation']) ?> · <?= e($staffInfo['department']?:'—') ?></p>
                <span style="background:rgba(255,255,255,0.2);color:white;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;">
                    <?= ucfirst(str_replace('_',' ',$staffInfo['shift'])) ?> Shift
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-list-check"></i></div>
            <div class="stat-num"><?= $myAssigned ?></div>
            <div class="stat-label">Newly Assigned</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-spinner"></i></div>
            <div class="stat-num"><?= $myInProgress ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-num"><?= $myResolved ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card danger">
            <div class="stat-icon-wrap danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-num"><?= $openCount ?></div>
            <div class="stat-label">Unassigned Open</div>
        </div>
    </div>
</div>

<!-- My Tasks -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-list-check"></i> My Active Tasks (<?= count($pendingTasks) ?>)</div>
        <a href="<?= BASE_URL ?>/staff/complaints.php" class="btn-sc outline-primary xs">View All</a>
    </div>
    <div class="table-responsive">
        <?php if (empty($pendingTasks)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-face-smile-beam"></i></div>
            <h5>All Clear!</h5><p>No active tasks assigned to you.</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead><tr><th>#</th><th>Complaint</th><th>Flat</th><th>Category</th><th>Priority</th><th>Status</th><th>Since</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($pendingTasks as $i => $t): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($t['title']) ?></div>
                    <small class="text-muted"><?= e($t['resident_name']) ?></small>
                </td>
                <td><span class="flat-pill"><?= e($t['flat_no']) ?></span></td>
                <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-<?= categoryIcon($t['category']) ?> me-1"></i><?= ucfirst($t['category']) ?></span></td>
                <td><?= statusBadge($t['priority']) ?></td>
                <td><?= statusBadge($t['status']) ?></td>
                <td><small><?= timeAgo($t['created_at']) ?></small></td>
                <td>
                    <a href="<?= BASE_URL ?>/staff/complaints.php?update=<?= $t['id'] ?>&status=in_progress"
                       class="btn-sc secondary xs" <?= $t['status']==='in_progress'?'style="opacity:0.5;pointer-events:none;"':'' ?>>
                        <i class="fa-solid fa-play"></i> Start
                    </a>
                    <a href="<?= BASE_URL ?>/staff/complaints.php?update=<?= $t['id'] ?>&status=resolved"
                       class="btn-sc success xs"
                       data-confirm="Mark as resolved?">
                        <i class="fa-solid fa-check"></i> Resolve
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

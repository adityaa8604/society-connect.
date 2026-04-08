<?php
/**
 * user/visitors.php — Resident visitor log
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

$resStmt = $db->prepare("SELECT r.*, f.flat_no, f.id as flat_id FROM residents r JOIN flats f ON r.flat_id=f.id WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();
if (!$resident) { header("Location: ".BASE_URL."/user/index.php"); exit; }

// ---- ADD PRE-APPROVED VISITOR ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor'])) {
    $name     = trim($_POST['visitor_name'] ?? '');
    $phone    = trim($_POST['phone']        ?? '');
    $purpose  = trim($_POST['purpose']      ?? '');

    if (empty($name)) { setFlash('error', 'Visitor name is required.'); }
    else {
        $ins = $db->prepare("INSERT INTO visitors (visitor_name,phone,purpose,flat_id,resident_id,status) VALUES (?,?,?,?,?,'pre_approved')");
        $ins->execute([$name,$phone,$purpose,$resident['flat_id'],$resident['id']]);
        setFlash('success', 'Visitor pre-approved! Security will be notified.');
    }
    header("Location: ".BASE_URL."/user/visitors.php"); exit;
}

// Fetch visitors for my flat
$visitors = $db->prepare("SELECT * FROM visitors WHERE flat_id=? ORDER BY entry_time DESC LIMIT 20");
$visitors->execute([$resident['flat_id']]);
$visitors = $visitors->fetchAll();

$showAdd = isset($_GET['action']) && $_GET['action'] === 'add';

$pageTitle  = 'My Visitors';
$activePage = 'visitors';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div><h4 class="fw-800 mb-1">Visitor Log</h4><p class="text-muted mb-0">Flat <?= e($resident['flat_no']) ?></p></div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
        <i class="fa-solid fa-user-plus"></i> Pre-Approve Visitor
    </button>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-door-open"></i> Visitors (<?= count($visitors) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($visitors)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-users"></i></div><h5>No Visitors</h5><p>No visitor records found.</p></div>
        <?php else: ?>
        <table class="table-sc">
            <thead><tr><th>#</th><th>Visitor Name</th><th>Phone</th><th>Purpose</th><th>Entry</th><th>Exit</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($visitors as $i => $v): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td class="fw-700"><?= e($v['visitor_name']) ?></td>
                <td><?= e($v['phone']?:'—') ?></td>
                <td><?= e($v['purpose']?:'—') ?></td>
                <td><?= $v['entry_time'] ? date('d M, h:i A',strtotime($v['entry_time'])) : '—' ?></td>
                <td><?= $v['exit_time']  ? date('d M, h:i A',strtotime($v['exit_time']))  : '—' ?></td>
                <td><?= statusBadge($v['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade <?= $showAdd?'show':'' ?>" id="addVisitorModal" tabindex="-1" <?= $showAdd?'style="display:block;"':'' ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-user-check me-2 text-primary"></i>Pre-Approve a Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Visitor Name *</label>
                            <input type="text" name="visitor_name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 9800000000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Purpose of Visit</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Delivery, Family visit, Service...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_visitor" class="btn-sc primary"><i class="fa-solid fa-user-check"></i> Pre-Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($showAdd): ?><div class="modal-backdrop fade show"></div><?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

<?php
/**
 * staff/visitors.php — Staff visitor entry
 */
require_once '../config/db.php';
requireAnyRole(['staff','admin']);
$db   = getDB();
$user = currentUser();

// ---- ADD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor'])) {
    $name    = trim($_POST['visitor_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $flatId  = (int)$_POST['flat_id'];
    $vehicle = trim($_POST['vehicle_no'] ?? '');

    $res = $db->prepare("SELECT id FROM residents WHERE flat_id=? LIMIT 1"); $res->execute([$flatId]);
    $resId = $res->fetchColumn() ?: null;

    $ins = $db->prepare("INSERT INTO visitors (visitor_name,phone,purpose,flat_id,resident_id,vehicle_no,logged_by) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([$name,$phone,$purpose,$flatId,$resId,$vehicle,$user['id']]);
    setFlash('success', 'Visitor logged!');
    header("Location: ".BASE_URL."/staff/visitors.php"); exit;
}

// ---- EXIT ----
if (isset($_GET['exit'])) {
    $db->prepare("UPDATE visitors SET exit_time=NOW(), status='exited' WHERE id=?")->execute([(int)$_GET['exit']]);
    setFlash('success', 'Exit recorded.');
    header("Location: ".BASE_URL."/staff/visitors.php"); exit;
}

$visitors = $db->query("SELECT v.*, f.flat_no FROM visitors v JOIN flats f ON v.flat_id=f.id
                         WHERE DATE(v.entry_time)=CURDATE() ORDER BY v.entry_time DESC")->fetchAll();
$allFlats = $db->query("SELECT id, flat_no FROM flats WHERE status='occupied' ORDER BY flat_no")->fetchAll();
$activeCount = $db->query("SELECT COUNT(*) FROM visitors WHERE status='inside'")->fetchColumn();

$pageTitle  = 'Visitor Log';
$activePage = 'visitors';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Today's Visitor Log</h4>
        <p class="text-muted mb-0"><?= $activeCount ?> visitor(s) currently inside</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fa-solid fa-user-plus"></i> Log Visitor
    </button>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-door-open"></i> Today's Visitors (<?= count($visitors) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($visitors)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-users"></i></div><h5>No Visitors Today</h5></div>
        <?php else: ?>
        <table class="table-sc">
            <thead><tr><th>#</th><th>Visitor</th><th>Flat</th><th>Purpose</th><th>Entry</th><th>Exit</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($visitors as $i => $v): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($v['visitor_name']) ?></div>
                    <small class="text-muted"><?= e($v['phone']?:'—') ?></small>
                </td>
                <td><span class="flat-pill"><?= e($v['flat_no']) ?></span></td>
                <td><?= e($v['purpose']?:'—') ?></td>
                <td><?= date('h:i A',strtotime($v['entry_time'])) ?></td>
                <td><?= $v['exit_time'] ? date('h:i A',strtotime($v['exit_time'])) : '—' ?></td>
                <td><?= statusBadge($v['status']) ?></td>
                <td>
                    <?php if ($v['status'] === 'inside'): ?>
                    <a href="?exit=<?= $v['id'] ?>" class="btn-sc warning xs" data-confirm="Record exit?">
                        <i class="fa-solid fa-door-closed"></i> Exit
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Log Visitor Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Visitor Name *</label>
                            <input type="text" name="visitor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Visiting Flat *</label>
                            <select name="flat_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($allFlats as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= e($f['flat_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Delivery, Visit...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle No.</label>
                            <input type="text" name="vehicle_no" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_visitor" class="btn-sc primary"><i class="fa-solid fa-door-open"></i> Log Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

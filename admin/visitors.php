<?php
/**
 * admin/visitors.php — Visitor Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD VISITOR ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor'])) {
    $name     = trim($_POST['visitor_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $purpose  = trim($_POST['purpose'] ?? '');
    $flatId   = (int)$_POST['flat_id'];
    $vehicle  = trim($_POST['vehicle_no'] ?? '');
    $idProof  = trim($_POST['id_proof'] ?? '');
    $userId   = currentUser()['id'];

    $res = $db->prepare("SELECT id FROM residents WHERE flat_id=? LIMIT 1");
    $res->execute([$flatId]);
    $resId = $res->fetchColumn() ?: null;

    $ins = $db->prepare("INSERT INTO visitors (visitor_name,phone,purpose,id_proof,flat_id,resident_id,vehicle_no,logged_by) VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([$name,$phone,$purpose,$idProof,$flatId,$resId,$vehicle,$userId]);
    logActivity($userId, "Visitor logged: $name", 'visitors');
    setFlash('success', 'Visitor entry logged!');
    header("Location: " . BASE_URL . "/admin/visitors.php"); exit;
}

// ---- EXIT VISITOR ----
if (isset($_GET['exit'])) {
    $id = (int)$_GET['exit'];
    $db->prepare("UPDATE visitors SET exit_time=NOW(), status='exited' WHERE id=?")->execute([$id]);
    setFlash('success', 'Exit time recorded.');
    header("Location: " . BASE_URL . "/admin/visitors.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM visitors WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Visitor record deleted.');
    header("Location: " . BASE_URL . "/admin/visitors.php"); exit;
}

// ---- FETCH ----
$filterStatus = $_GET['status'] ?? 'all';
$filterDate   = $_GET['date']   ?? date('Y-m-d');

$sql    = "SELECT v.*, f.flat_no, u.name as logged_by_name
           FROM visitors v
           JOIN flats f ON v.flat_id = f.id
           LEFT JOIN users u ON v.logged_by = u.id";
$where  = ["DATE(v.entry_time)=?"];
$params = [$filterDate];
if ($filterStatus !== 'all') { $where[] = "v.status=?"; $params[] = $filterStatus; }
$sql .= " WHERE " . implode(' AND ', $where) . " ORDER BY v.entry_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$visitors = $stmt->fetchAll();

$activeCount = $db->query("SELECT COUNT(*) FROM visitors WHERE status='inside'")->fetchColumn();
$todayCount  = $db->query("SELECT COUNT(*) FROM visitors WHERE DATE(entry_time)=CURDATE()")->fetchColumn();
$totalCount  = $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();

$allFlats = $db->query("SELECT id, flat_no FROM flats WHERE status='occupied' ORDER BY flat_no")->fetchAll();

$pageTitle = 'Visitor Management';
$activePage= 'visitors';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Visitor Management</h4>
        <p class="text-muted mb-0">Log and track all society visitors</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
        <i class="fa-solid fa-user-plus"></i> Log Visitor
    </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
            <div class="stat-num"><?= $activeCount ?></div>
            <div class="stat-label">Currently Inside</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="stat-num"><?= $todayCount ?></div>
            <div class="stat-label">Today's Visitors</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-users"></i></div>
            <div class="stat-num"><?= $totalCount ?></div>
            <div class="stat-label">Total All Time</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="sc-card mb-4">
    <div class="sc-card-body py-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= e($filterDate) ?>">
            </div>
            <div>
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" style="width:130px;">
                    <option value="all"    <?= $filterStatus==='all'?'selected':'' ?>>All</option>
                    <option value="inside" <?= $filterStatus==='inside'?'selected':'' ?>>Inside</option>
                    <option value="exited" <?= $filterStatus==='exited'?'selected':'' ?>>Exited</option>
                </select>
            </div>
            <button type="submit" class="btn-sc primary sm"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="<?= BASE_URL ?>/admin/visitors.php" class="btn-sc light sm">Reset</a>
        </form>
    </div>
</div>

<!-- Visitors Table -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-door-open"></i> Visitor Log (<?= count($visitors) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($visitors)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
            <h5>No Visitors</h5>
            <p>No visitor records for <?= date('d M Y', strtotime($filterDate)) ?>.</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Visitor</th><th>Visiting</th><th>Purpose</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($visitors as $i => $v): ?>
            <?php
                $duration = '—';
                if ($v['exit_time']) {
                    $diff = strtotime($v['exit_time']) - strtotime($v['entry_time']);
                    $duration = floor($diff/3600) . 'h ' . floor(($diff%3600)/60) . 'm';
                } elseif ($v['status'] === 'inside') {
                    $diff = time() - strtotime($v['entry_time']);
                    $duration = '<span class="text-success fw-700">' . floor($diff/3600) . 'h ' . floor(($diff%3600)/60) . 'm ↑</span>';
                }
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($v['visitor_name']) ?></div>
                    <small class="text-muted"><?= e($v['phone'] ?: '—') ?></small>
                </td>
                <td><span class="flat-pill"><?= e($v['flat_no']) ?></span></td>
                <td><?= e($v['purpose'] ?: '—') ?></td>
                <td><small><?= date('h:i A', strtotime($v['entry_time'])) ?></small></td>
                <td><small><?= $v['exit_time'] ? date('h:i A', strtotime($v['exit_time'])) : '—' ?></small></td>
                <td><?= $duration ?></td>
                <td><?= statusBadge($v['status']) ?></td>
                <td>
                    <?php if ($v['status'] === 'inside'): ?>
                    <a href="?exit=<?= $v['id'] ?>&date=<?= e($filterDate) ?>"
                       class="btn-sc warning xs" data-confirm="Record exit for this visitor?">
                        <i class="fa-solid fa-door-closed"></i> Exit
                    </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $v['id'] ?>&date=<?= e($filterDate) ?>"
                       class="btn-sc danger xs" data-confirm="Delete this visitor record?">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Visitor Modal -->
<div class="modal fade" id="addVisitorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Log Visitor Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Visitor Name *</label>
                            <input type="text" name="visitor_name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 9800000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Visiting Flat *</label>
                            <select name="flat_id" class="form-select" required>
                                <option value="">-- Select Flat --</option>
                                <?php foreach ($allFlats as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= e($f['flat_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Delivery, Visit, Repair...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle Number</label>
                            <input type="text" name="vehicle_no" class="form-control" placeholder="MH-01-AB-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID Proof Type</label>
                            <select name="id_proof" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach (['Aadhaar','PAN','Driving License','Passport','Voter ID'] as $id): ?>
                                <option value="<?= $id ?>"><?= $id ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_visitor" class="btn-sc primary">
                        <i class="fa-solid fa-door-open"></i> Log Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

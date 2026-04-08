<?php
/**
 * admin/flats.php — Flat Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD FLAT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_flat'])) {
    $ins = $db->prepare("INSERT INTO flats (flat_no,block,floor,type,area_sqft,status) VALUES (?,?,?,?,?,?)");
    $ins->execute([
        trim($_POST['flat_no'] ?? ''),
        trim($_POST['block']   ?? ''),
        (int)($_POST['floor']  ?? 0),
        $_POST['type']   ?? '2BHK',
        (int)($_POST['area_sqft'] ?? 0),
        $_POST['status'] ?? 'vacant',
    ]);
    setFlash('success', 'Flat added!');
    header("Location: " . BASE_URL . "/admin/flats.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $chk = $db->prepare("SELECT id FROM residents WHERE flat_id=? LIMIT 1");
    $chk->execute([$id]);
    if ($chk->fetch()) {
        setFlash('error', 'Cannot delete flat with active residents.');
    } else {
        $db->prepare("DELETE FROM flats WHERE id=?")->execute([$id]);
        setFlash('success', 'Flat deleted.');
    }
    header("Location: " . BASE_URL . "/admin/flats.php"); exit;
}

// ---- FETCH ----
$flats = $db->query("SELECT f.*, r.id as res_id, u.name as resident_name, r.resident_type
                     FROM flats f
                     LEFT JOIN residents r ON f.id=r.flat_id
                     LEFT JOIN users u ON r.user_id=u.id
                     ORDER BY f.block, f.flat_no")->fetchAll();

$occupiedCount   = count(array_filter($flats, fn($f) => $f['status']==='occupied'));
$vacantCount     = count(array_filter($flats, fn($f) => $f['status']==='vacant'));
$maintenanceCount= count(array_filter($flats, fn($f) => $f['status']==='under_maintenance'));

$pageTitle = 'Flats Management';
$activePage= 'flats';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Flats Management</h4>
        <p class="text-muted mb-0">Manage all flats and occupancy status</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addFlatModal">
        <i class="fa-solid fa-plus"></i> Add Flat
    </button>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-building"></i></div>
            <div class="stat-num"><?= count($flats) ?></div>
            <div class="stat-label">Total Flats</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-house-chimney-user"></i></div>
            <div class="stat-num"><?= $occupiedCount ?></div>
            <div class="stat-label">Occupied</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card secondary">
            <div class="stat-icon-wrap secondary"><i class="fa-solid fa-house-chimney"></i></div>
            <div class="stat-num"><?= $vacantCount ?></div>
            <div class="stat-label">Vacant</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div class="stat-num"><?= $maintenanceCount ?></div>
            <div class="stat-label">Under Maintenance</div>
        </div>
    </div>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-building"></i> All Flats (<?= count($flats) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Flat No</th><th>Block</th><th>Floor</th><th>Type</th><th>Area</th><th>Occupant</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($flats as $i => $f): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="flat-pill"><?= e($f['flat_no']) ?></span></td>
                <td><span class="badge bg-light text-dark border">Block <?= e($f['block']) ?></span></td>
                <td><?= $f['floor'] == 0 ? 'Ground' : 'Floor ' . $f['floor'] ?></td>
                <td><?= e($f['type']) ?></td>
                <td><?= $f['area_sqft'] ? $f['area_sqft'] . ' sqft' : '—' ?></td>
                <td>
                    <?php if ($f['resident_name']): ?>
                    <div class="fw-600"><?= e($f['resident_name']) ?></div>
                    <small><?= statusBadge($f['resident_type'] ?? 'owner') ?></small>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= statusBadge($f['status']) ?></td>
                <td>
                    <?php if (!$f['res_id']): ?>
                    <a href="?delete=<?= $f['id'] ?>" class="btn-sc danger xs"
                       data-confirm="Delete flat <?= e($f['flat_no']) ?>?">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted small">Has resident</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Flat Modal -->
<div class="modal fade" id="addFlatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-building me-2 text-primary"></i>Add New Flat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Flat Number *</label>
                            <input type="text" name="flat_no" class="form-control" placeholder="e.g. A-101" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Block *</label>
                            <input type="text" name="block" class="form-control" placeholder="e.g. A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Floor</label>
                            <input type="number" name="floor" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <?php foreach (['1BHK','2BHK','3BHK','4BHK','Penthouse'] as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Area (sqft)</label>
                            <input type="number" name="area_sqft" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="vacant">Vacant</option>
                                <option value="occupied">Occupied</option>
                                <option value="under_maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_flat" class="btn-sc primary">
                        <i class="fa-solid fa-plus"></i> Add Flat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

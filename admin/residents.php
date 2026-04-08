<?php
/**
 * admin/residents.php — Manage Residents
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD RESIDENT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resident'])) {
    $uid    = (int)$_POST['user_id'];
    $flatId = (int)$_POST['flat_id'];
    $type   = $_POST['resident_type'] ?? 'owner';
    $count  = (int)($_POST['members_count'] ?? 1);
    $vehicle= trim($_POST['vehicles'] ?? '');
    $movein = $_POST['move_in_date'] ?? null;
    $emrg   = trim($_POST['emergency_contact'] ?? '');

    // Check if user already a resident
    $chk = $db->prepare("SELECT id FROM residents WHERE user_id=? LIMIT 1");
    $chk->execute([$uid]);
    if ($chk->fetch()) {
        setFlash('error', 'This user is already registered as a resident.');
    } else {
        $ins = $db->prepare("INSERT INTO residents (user_id,flat_id,resident_type,members_count,vehicles,move_in_date,emergency_contact) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$uid,$flatId,$type,$count,$vehicle,$movein ?: null,$emrg]);
        // Mark flat occupied
        $db->prepare("UPDATE flats SET status='occupied' WHERE id=?")->execute([$flatId]);
        logActivity(currentUser()['id'], "Added resident for flat $flatId", 'residents');
        setFlash('success', 'Resident added successfully!');
    }
    header("Location: " . BASE_URL . "/admin/residents.php"); exit;
}

// ---- DELETE RESIDENT ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get flat id to mark as vacant
    $r = $db->prepare("SELECT flat_id FROM residents WHERE id=?");
    $r->execute([$id]);
    $row = $r->fetch();
    if ($row) {
        $db->prepare("DELETE FROM residents WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE flats SET status='vacant' WHERE id=?")->execute([$row['flat_id']]);
        setFlash('success', 'Resident removed.');
    }
    header("Location: " . BASE_URL . "/admin/residents.php"); exit;
}

// ---- FETCH DATA ----
$search = trim($_GET['search'] ?? '');
$sql = "SELECT r.*, u.name, u.email, u.phone, f.flat_no, f.block, f.type as flat_type
        FROM residents r
        JOIN users u ON r.user_id = u.id
        JOIN flats f ON r.flat_id = f.id";
if ($search) {
    $sql .= " WHERE u.name LIKE ? OR f.flat_no LIKE ? OR u.phone LIKE ?";
}
$sql .= " ORDER BY f.flat_no";
$stmt = $db->prepare($sql);
$stmt->execute($search ? ["%$search%","%$search%","%$search%"] : []);
$residents = $stmt->fetchAll();

// Users without resident record
$availableUsers = $db->query("SELECT id, name, email FROM users WHERE role='resident' AND id NOT IN (SELECT user_id FROM residents) ORDER BY name")->fetchAll();
// Vacant flats
$vacantFlats = $db->query("SELECT id, flat_no, block, type FROM flats WHERE status='vacant' ORDER BY flat_no")->fetchAll();

$pageTitle = 'Residents Management';
$activePage= 'residents';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Residents <span class="badge bg-primary ms-2"><?= count($residents) ?></span></h4>
        <p class="text-muted mb-0">Manage all society residents and flat assignments</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addResidentModal">
        <i class="fa-solid fa-user-plus"></i> Add Resident
    </button>
</div>

<!-- Search -->
<div class="sc-card mb-4">
    <div class="sc-card-body py-3">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search by name, flat, phone..."
                   value="<?= e($search) ?>" style="max-width:360px;">
            <button type="submit" class="btn-sc primary sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/admin/residents.php" class="btn-sc light sm">✕ Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Residents Table -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-users"></i> All Residents</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($residents)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
            <h5>No Residents Found</h5>
            <p><?= $search ? 'Try a different search.' : 'Add your first resident to get started.' ?></p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr>
                    <th>#</th><th>Name</th><th>Flat</th><th>Type</th>
                    <th>Members</th><th>Phone</th><th>Move-In</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($residents as $i => $r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar-sm" style="width:34px;height:34px;font-size:13px;">
                            <?= strtoupper(substr($r['name'],0,1)) ?>
                        </div>
                        <div>
                            <div class="fw-700"><?= e($r['name']) ?></div>
                            <small class="text-muted"><?= e($r['email']) ?></small>
                        </div>
                    </div>
                </td>
                <td><span class="flat-pill"><?= e($r['flat_no']) ?></span> <small class="text-muted">(<?= e($r['flat_type']) ?>)</small></td>
                <td><?= statusBadge($r['resident_type']) ?></td>
                <td><?= $r['members_count'] ?></td>
                <td><?= e($r['phone'] ?: '—') ?></td>
                <td><?= $r['move_in_date'] ? date('d M Y', strtotime($r['move_in_date'])) : '—' ?></td>
                <td>
                    <a href="?delete=<?= $r['id'] ?>" class="btn-sc danger xs"
                       data-confirm="Remove this resident? Their flat will be marked as vacant.">
                       <i class="fa-solid fa-trash-can"></i> Remove
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Resident Modal -->
<div class="modal fade" id="addResidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add New Resident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select User *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Choose User --</option>
                                <?php foreach ($availableUsers as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Only users not yet assigned as residents</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign Flat *</label>
                            <select name="flat_id" class="form-select" required>
                                <option value="">-- Select Flat --</option>
                                <?php foreach ($vacantFlats as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= e($f['flat_no']) ?> - <?= e($f['type']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Resident Type</label>
                            <select name="resident_type" class="form-select">
                                <option value="owner">Owner</option>
                                <option value="tenant">Tenant</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Family Members</label>
                            <input type="number" name="members_count" class="form-control" value="1" min="1" max="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Move-In Date</label>
                            <input type="date" name="move_in_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle Numbers</label>
                            <input type="text" name="vehicles" class="form-control" placeholder="MH-01-AB-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control" placeholder="Phone number">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_resident" class="btn-sc primary">
                        <i class="fa-solid fa-user-plus"></i> Add Resident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

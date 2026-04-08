<?php
/**
 * admin/staff.php — Staff Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD STAFF ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $userId      = (int)$_POST['user_id'];
    $designation = trim($_POST['designation'] ?? '');
    $department  = trim($_POST['department']  ?? '');
    $salary      = (float)($_POST['salary']   ?? 0);
    $joinDate    = $_POST['join_date'] ?? null;
    $shift       = $_POST['shift'] ?? 'full_day';

    $chk = $db->prepare("SELECT id FROM staff WHERE user_id=? LIMIT 1");
    $chk->execute([$userId]);
    if ($chk->fetch()) {
        setFlash('error', 'This user is already a staff member.');
    } else {
        $ins = $db->prepare("INSERT INTO staff (user_id,designation,department,salary,join_date,shift) VALUES (?,?,?,?,?,?)");
        $ins->execute([$userId,$designation,$department,$salary,$joinDate ?: null,$shift]);
        // Update user role to staff
        $db->prepare("UPDATE users SET role='staff' WHERE id=?")->execute([$userId]);
        logActivity(currentUser()['id'], "Staff member added: $designation", 'staff');
        setFlash('success', 'Staff member added!');
    }
    header("Location: " . BASE_URL . "/admin/staff.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $s  = $db->prepare("SELECT user_id FROM staff WHERE id=?"); $s->execute([$id]);
    $row= $s->fetch();
    if ($row) {
        $db->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE users SET role='resident' WHERE id=?")->execute([$row['user_id']]);
    }
    setFlash('success', 'Staff member removed.');
    header("Location: " . BASE_URL . "/admin/staff.php"); exit;
}

// ---- FETCH ----
$staffList = $db->query("SELECT s.*, u.name, u.email, u.phone, u.status
                         FROM staff s JOIN users u ON s.user_id=u.id
                         ORDER BY u.name")->fetchAll();

// Available users (not yet staff)
$availableUsers = $db->query("SELECT id, name, email FROM users
                               WHERE id NOT IN (SELECT user_id FROM staff)
                               AND role != 'admin'
                               ORDER BY name")->fetchAll();

$pageTitle = 'Staff Management';
$activePage= 'staff';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Staff Management</h4>
        <p class="text-muted mb-0">Manage society staff members and assignments</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="fa-solid fa-user-plus"></i> Add Staff
    </button>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-user-tie"></i> Staff Members (<?= count($staffList) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($staffList)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-user-tie"></i></div>
            <h5>No Staff Members</h5><p>Add staff to get started.</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Staff Member</th><th>Designation</th><th>Department</th><th>Shift</th><th>Salary</th><th>Join Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($staffList as $i => $s): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar-sm" style="width:34px;height:34px;font-size:13px;background:linear-gradient(135deg,#06B6D4,#0891B2);">
                            <?= strtoupper(substr($s['name'],0,1)) ?>
                        </div>
                        <div>
                            <div class="fw-700"><?= e($s['name']) ?></div>
                            <small class="text-muted"><?= e($s['email']) ?></small>
                        </div>
                    </div>
                </td>
                <td class="fw-600"><?= e($s['designation']) ?></td>
                <td><?= e($s['department'] ?: '—') ?></td>
                <td><span class="badge bg-light text-dark border"><?= ucfirst(str_replace('_',' ',$s['shift'])) ?></span></td>
                <td class="fw-700"><?= formatCurrency($s['salary']) ?></td>
                <td><?= $s['join_date'] ? date('d M Y', strtotime($s['join_date'])) : '—' ?></td>
                <td><?= statusBadge($s['status']) ?></td>
                <td>
                    <a href="?delete=<?= $s['id'] ?>" class="btn-sc danger xs"
                       data-confirm="Remove this staff member?">
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add Staff Member</h5>
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
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation *</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Security Guard" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach (['Security','Maintenance','Housekeeping','Administration','Gardening','Electrician'] as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift" class="form-select">
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                                <option value="night">Night</option>
                                <option value="full_day" selected>Full Day</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monthly Salary (₹)</label>
                            <input type="number" name="salary" class="form-control" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Join Date</label>
                            <input type="date" name="join_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_staff" class="btn-sc primary">
                        <i class="fa-solid fa-user-plus"></i> Add Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

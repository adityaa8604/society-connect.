<?php
/**
 * admin/complaints.php — Complaints Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- UPDATE STATUS / ASSIGN ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint'])) {
    $id      = (int)$_POST['complaint_id'];
    $status  = $_POST['status'] ?? 'open';
    $assign  = !empty($_POST['assign_to']) ? (int)$_POST['assign_to'] : null;
    $remarks = trim($_POST['remarks'] ?? '');
    $resolved = ($status === 'resolved') ? date('Y-m-d H:i:s') : null;

    $stmt = $db->prepare("UPDATE complaints SET status=?, assigned_to=?, remarks=?, resolved_at=? WHERE id=?");
    $stmt->execute([$status, $assign, $remarks, $resolved, $id]);
    logActivity(currentUser()['id'], "Complaint #$id updated to $status", 'complaints');
    setFlash('success', 'Complaint updated successfully!');
    header("Location: " . BASE_URL . "/admin/complaints.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM complaints WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Complaint deleted.');
    header("Location: " . BASE_URL . "/admin/complaints.php"); exit;
}

// ---- FETCH ----
$filterStatus   = $_GET['status']   ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';
$filterPriority = $_GET['priority'] ?? 'all';

$sql    = "SELECT c.*, f.flat_no, u.name as resident_name, s.designation,
                  us.name as staff_name
           FROM complaints c
           JOIN residents r ON c.resident_id = r.id
           JOIN users u ON r.user_id = u.id
           JOIN flats f ON c.flat_id = f.id
           LEFT JOIN staff s ON c.assigned_to = s.id
           LEFT JOIN users us ON s.user_id = us.id";
$where  = [];
$params = [];
if ($filterStatus   !== 'all') { $where[] = "c.status=?";   $params[] = $filterStatus; }
if ($filterCategory !== 'all') { $where[] = "c.category=?"; $params[] = $filterCategory; }
if ($filterPriority !== 'all') { $where[] = "c.priority=?"; $params[] = $filterPriority; }
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY FIELD(c.priority,'critical','high','medium','low'), c.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Count by status
$statusCounts = $db->query("SELECT status, COUNT(*) cnt FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$staffList    = $db->query("SELECT s.id, u.name, s.designation FROM staff s JOIN users u ON s.user_id=u.id ORDER BY u.name")->fetchAll();

$categories = ['electrical','plumbing','civil','housekeeping','security','lift','common_area','other'];
$priorities  = ['low','medium','high','critical'];

$pageTitle = 'Complaints Management';
$activePage= 'complaints';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Complaints Management</h4>
        <p class="text-muted mb-0">Track and resolve resident complaints</p>
    </div>
</div>

<!-- Status Summary Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php
    $statusPills = ['all'=>['All','secondary'], 'open'=>['Open','danger'], 'assigned'=>['Assigned','warning'],
                    'in_progress'=>['In Progress','info'], 'resolved'=>['Resolved','success'], 'closed'=>['Closed','secondary']];
    foreach ($statusPills as $val => [$label, $color]): ?>
    <a href="?status=<?= $val ?>&category=<?= $filterCategory ?>&priority=<?= $filterPriority ?>"
       class="badge bg-<?= $filterStatus === $val ? $color : 'light' ?> text-<?= $filterStatus === $val ? 'white' : $color ?>"
       style="font-size:13px;padding:8px 16px;border:2px solid var(--bs-<?= $color ?>);text-decoration:none;">
        <?= $label ?>
        <?php if ($val !== 'all' && isset($statusCounts[$val])): ?>
        (<?= $statusCounts[$val] ?>)
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="sc-card mb-4">
    <div class="sc-card-body py-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            <select name="category" class="form-select form-select-sm" style="width:160px;">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c ?>" <?= $filterCategory===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" class="form-select form-select-sm" style="width:130px;">
                <option value="all">All Priorities</option>
                <?php foreach ($priorities as $p): ?>
                <option value="<?= $p ?>" <?= $filterPriority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-sc primary sm"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<!-- Complaints -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-triangle-exclamation"></i> Complaints (<?= count($complaints) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($complaints)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-face-smile"></i></div>
            <h5>No Complaints Found</h5>
            <p>No complaints match the selected filters.</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Complaint</th><th>Flat</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($complaints as $i => $c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($c['title']) ?></div>
                    <small class="text-muted"><?= e($c['resident_name']) ?></small>
                    <?php if ($c['remarks']): ?>
                    <div class="mt-1"><small class="text-info"><i class="fa-solid fa-message me-1"></i><?= e(substr($c['remarks'],0,60)) ?>...</small></div>
                    <?php endif; ?>
                </td>
                <td><span class="flat-pill"><?= e($c['flat_no']) ?></span></td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <i class="fa-solid fa-<?= categoryIcon($c['category']) ?> me-1"></i>
                        <?= ucfirst($c['category']) ?>
                    </span>
                </td>
                <td><?= statusBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><?= $c['staff_name'] ? e($c['staff_name']) : '<span class="text-muted">—</span>' ?></td>
                <td><small><?= timeAgo($c['created_at']) ?></small></td>
                <td>
                    <button class="btn-sc primary xs" data-bs-toggle="modal"
                            data-bs-target="#updateModal"
                            data-id="<?= $c['id'] ?>"
                            data-status="<?= e($c['status']) ?>"
                            data-assign="<?= (int)$c['assigned_to'] ?>"
                            data-remarks="<?= e($c['remarks'] ?? '') ?>">
                        <i class="fa-solid fa-pen"></i> Update
                    </button>
                    <a href="?delete=<?= $c['id'] ?>&status=<?= $filterStatus ?>"
                       class="btn-sc danger xs" data-confirm="Delete this complaint?">
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

<!-- Update Complaint Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-pen me-2 text-primary"></i>Update Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="complaint_id" id="modalComplaintId">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="modalStatus" class="form-select">
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To Staff</label>
                        <select name="assign_to" id="modalAssign" class="form-select">
                            <option value="">-- Not Assigned --</option>
                            <?php foreach ($staffList as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['designation']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks / Notes</label>
                        <textarea name="remarks" id="modalRemarks" class="form-control" rows="3" placeholder="Add a note or resolution details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_complaint" class="btn-sc primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate modal with complaint data
document.getElementById('updateModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalComplaintId').value = btn.dataset.id;
    document.getElementById('modalStatus').value      = btn.dataset.status;
    document.getElementById('modalAssign').value      = btn.dataset.assign;
    document.getElementById('modalRemarks').value     = btn.dataset.remarks;
});
</script>

<?php require_once '../includes/footer.php'; ?>

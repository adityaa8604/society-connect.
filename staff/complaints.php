<?php
/**
 * staff/complaints.php — Staff task management
 */
require_once '../config/db.php';
requireLogin('staff');
$db   = getDB();
$user = currentUser();

$staffStmt = $db->prepare("SELECT id FROM staff WHERE user_id=? LIMIT 1");
$staffStmt->execute([$user['id']]);
$staffId = $staffStmt->fetchColumn();

// ---- UPDATE STATUS via GET ----
if (isset($_GET['update']) && isset($_GET['status'])) {
    $id     = (int)$_GET['update'];
    $status = $_GET['status'];
    $allowed = ['in_progress','resolved','closed'];
    if (in_array($status, $allowed)) {
        $resolved = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
        $db->prepare("UPDATE complaints SET status=?, resolved_at=? WHERE id=? AND assigned_to=?")
           ->execute([$status,$resolved,$id,$staffId]);
        setFlash('success', 'Task status updated to ' . ucfirst($status) . '!');
    }
    header("Location: ".BASE_URL."/staff/complaints.php"); exit;
}

// ---- UPDATE with remarks via POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $id      = (int)$_POST['complaint_id'];
    $status  = $_POST['status'] ?? 'in_progress';
    $remarks = trim($_POST['remarks'] ?? '');
    $resolved = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
    $db->prepare("UPDATE complaints SET status=?, remarks=?, resolved_at=? WHERE id=? AND assigned_to=?")
       ->execute([$status,$remarks,$resolved,$id,$staffId]);
    setFlash('success', 'Task updated!');
    header("Location: ".BASE_URL."/staff/complaints.php"); exit;
}

$filterStatus = $_GET['status'] ?? 'active';
$sql = "SELECT c.*, f.flat_no, u.name as resident_name
        FROM complaints c
        JOIN residents r ON c.resident_id=r.id
        JOIN users u ON r.user_id=u.id
        JOIN flats f ON c.flat_id=f.id
        WHERE c.assigned_to=?";
if ($filterStatus === 'active') { $sql .= " AND c.status IN('assigned','in_progress')"; }
elseif ($filterStatus !== 'all') { $sql .= " AND c.status='$filterStatus'"; }
$sql .= " ORDER BY FIELD(c.priority,'critical','high','medium','low'), c.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$staffId]);
$tasks = $stmt->fetchAll();

$pageTitle  = 'My Tasks';
$activePage = 'complaints';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-800">My Assigned Tasks</h4>
</div>

<!-- Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach (['active'=>['Active','warning'],'all'=>['All','secondary'],'resolved'=>['Resolved','success'],'closed'=>['Closed','primary']] as $val=>[$label,$color]): ?>
    <a href="?status=<?= $val ?>"
       class="btn-sc <?= $filterStatus===$val?$color:'light' ?> sm">
       <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-list-check"></i> Tasks (<?= count($tasks) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($tasks)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-circle-check"></i></div><h5>No Tasks</h5><p>No tasks match this filter.</p></div>
        <?php else: ?>
        <table class="table-sc">
            <thead><tr><th>#</th><th>Complaint</th><th>Flat</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($tasks as $i => $t): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($t['title']) ?></div>
                    <small class="text-muted"><?= e($t['resident_name']) ?> · <?= e(substr($t['description'],0,60)) ?>...</small>
                    <?php if ($t['remarks']): ?><div class="mt-1"><small class="text-info"><i class="fa-solid fa-message me-1"></i><?= e(substr($t['remarks'],0,60)) ?></small></div><?php endif; ?>
                </td>
                <td><span class="flat-pill"><?= e($t['flat_no']) ?></span></td>
                <td><?= statusBadge($t['priority']) ?></td>
                <td><?= statusBadge($t['status']) ?></td>
                <td><small><?= timeAgo($t['created_at']) ?></small></td>
                <td style="white-space:nowrap;">
                    <?php if ($t['status'] === 'assigned'): ?>
                    <a href="?update=<?= $t['id'] ?>&status=in_progress" class="btn-sc secondary xs">
                        <i class="fa-solid fa-play"></i> Start
                    </a>
                    <?php endif; ?>
                    <?php if (in_array($t['status'],['assigned','in_progress'])): ?>
                    <button class="btn-sc success xs" data-bs-toggle="modal" data-bs-target="#updateModal"
                            data-id="<?= $t['id'] ?>" data-status="<?= e($t['status']) ?>"
                            data-remarks="<?= e($t['remarks']??'') ?>">
                        <i class="fa-solid fa-check"></i> Update
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-pen me-2 text-primary"></i>Update Task Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="complaint_id" id="modalId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" id="modalStatus" class="form-select">
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks / Resolution Notes</label>
                        <textarea name="remarks" id="modalRemarks" class="form-control" rows="3"
                                  placeholder="Describe what was done to resolve the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_task" class="btn-sc primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('updateModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalId').value       = btn.dataset.id;
    document.getElementById('modalStatus').value   = btn.dataset.status === 'in_progress' ? 'resolved' : btn.dataset.status;
    document.getElementById('modalRemarks').value  = btn.dataset.remarks;
});
</script>

<?php require_once '../includes/footer.php'; ?>

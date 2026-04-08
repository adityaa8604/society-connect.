<?php
/**
 * user/complaints.php — Resident Complaints
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

$resStmt = $db->prepare("SELECT r.*, f.flat_no, f.id as flat_id FROM residents r JOIN flats f ON r.flat_id=f.id WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();
if (!$resident) { header("Location: ".BASE_URL."/user/index.php"); exit; }

// ---- ADD COMPLAINT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_complaint'])) {
    $title    = trim($_POST['title']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'other';
    $priority = $_POST['priority'] ?? 'medium';

    if (empty($title) || empty($desc)) {
        setFlash('error', 'Title and description are required.');
    } else {
        $ins = $db->prepare("INSERT INTO complaints (resident_id,flat_id,title,description,category,priority) VALUES (?,?,?,?,?,?)");
        $ins->execute([$resident['id'],$resident['flat_id'],$title,$desc,$category,$priority]);
        logActivity($user['id'], "Complaint filed: $title", 'complaints');
        setFlash('success', 'Complaint submitted! We will look into it.');
    }
    header("Location: ".BASE_URL."/user/complaints.php"); exit;
}

// Fetch my complaints
$complaints = $db->prepare("SELECT c.*, s.designation, us.name as staff_name
                             FROM complaints c
                             LEFT JOIN staff s ON c.assigned_to=s.id
                             LEFT JOIN users us ON s.user_id=us.id
                             WHERE c.resident_id=? ORDER BY c.created_at DESC");
$complaints->execute([$resident['id']]);
$complaints = $complaints->fetchAll();

$categories = ['electrical','plumbing','civil','housekeeping','security','lift','common_area','other'];
$priorities  = ['low','medium','high','critical'];
$showAdd     = isset($_GET['action']) && $_GET['action'] === 'add';

$pageTitle  = 'My Complaints';
$activePage = 'complaints';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">My Complaints</h4>
        <p class="text-muted mb-0">Flat <?= e($resident['flat_no']) ?></p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addComplaintModal">
        <i class="fa-solid fa-plus"></i> New Complaint
    </button>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-triangle-exclamation"></i> My Complaints (<?= count($complaints) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($complaints)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-face-smile"></i></div>
            <h5>No Complaints</h5>
            <p>You haven't lodged any complaints. Everything looks good!</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Remarks</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($complaints as $i => $c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($c['title']) ?></div>
                    <small class="text-muted"><?= e(substr($c['description'],0,60)) ?>...</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <i class="fa-solid fa-<?= categoryIcon($c['category']) ?> me-1"></i>
                        <?= ucfirst($c['category']) ?>
                    </span>
                </td>
                <td><?= statusBadge($c['priority']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td><?= $c['staff_name'] ? e($c['staff_name']) : '<span class="text-muted">Not assigned</span>' ?></td>
                <td>
                    <?php if ($c['remarks']): ?>
                    <span class="text-info small" title="<?= e($c['remarks']) ?>">
                        <i class="fa-solid fa-message me-1"></i><?= e(substr($c['remarks'],0,30)) ?>...
                    </span>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><small><?= timeAgo($c['created_at']) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add Complaint Modal -->
<div class="modal fade <?= $showAdd?'show':'' ?>" id="addComplaintModal" tabindex="-1" <?= $showAdd?'style="display:block;"':'' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Lodge a Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Complaint Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Brief summary of the issue" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Describe the issue in detail..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_complaint" class="btn-sc danger">
                        <i class="fa-solid fa-paper-plane"></i> Submit Complaint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($showAdd): ?><div class="modal-backdrop fade show"></div><?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

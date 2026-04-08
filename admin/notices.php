<?php
/**
 * admin/notices.php — Notice Board Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD NOTICE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notice'])) {
    $title    = trim($_POST['title']    ?? '');
    $content  = trim($_POST['content']  ?? '');
    $category = trim($_POST['category'] ?? 'general');
    $pinned   = isset($_POST['is_pinned']) ? 1 : 0;
    $expiry   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $userId   = currentUser()['id'];

    $ins = $db->prepare("INSERT INTO notices (title,content,category,posted_by,is_pinned,expiry_date) VALUES (?,?,?,?,?,?)");
    $ins->execute([$title,$content,$category,$userId,$pinned,$expiry]);
    logActivity($userId, "Notice posted: $title", 'notices');
    setFlash('success', 'Notice posted successfully!');
    header("Location: " . BASE_URL . "/admin/notices.php"); exit;
}

// ---- TOGGLE ACTIVE ----
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $db->prepare("SELECT is_active FROM notices WHERE id=?"); $cur->execute([$id]);
    $val = $cur->fetchColumn();
    $db->prepare("UPDATE notices SET is_active=? WHERE id=?")->execute([$val?0:1, $id]);
    setFlash('success', 'Notice status updated.');
    header("Location: " . BASE_URL . "/admin/notices.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM notices WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Notice deleted.');
    header("Location: " . BASE_URL . "/admin/notices.php"); exit;
}

// ---- FETCH ----
$notices = $db->query("SELECT n.*, u.name as posted_by_name
                       FROM notices n JOIN users u ON n.posted_by=u.id
                       ORDER BY n.is_pinned DESC, n.created_at DESC")->fetchAll();

$categories = ['general','urgent','maintenance','event','finance','security'];

$pageTitle = 'Notice Board';
$activePage= 'notices';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Notice Board</h4>
        <p class="text-muted mb-0">Post and manage society notices and announcements</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
        <i class="fa-solid fa-plus"></i> Post Notice
    </button>
</div>

<!-- Notices Grid -->
<?php if (empty($notices)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fa-solid fa-bullhorn"></i></div>
    <h5>No Notices</h5><p>Post your first notice to inform residents.</p>
</div>
<?php else: ?>

<!-- Table view -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-bullhorn"></i> All Notices (<?= count($notices) ?>)</div>
    </div>
    <div class="table-responsive">
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Title</th><th>Category</th><th>Posted By</th><th>Views</th><th>Pinned</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($notices as $i => $n): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($n['title']) ?></div>
                    <small class="text-muted"><?= e(substr($n['content'],0,60)) ?>...</small>
                </td>
                <td>
                    <span class="badge bg-<?= $n['category']==='urgent'?'danger':($n['category']==='event'?'warning':'primary') ?>">
                        <i class="fa-solid fa-<?= categoryIcon($n['category']) ?> me-1"></i>
                        <?= ucfirst($n['category']) ?>
                    </span>
                </td>
                <td><?= e($n['posted_by_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= $n['views'] ?> 👁</span></td>
                <td><?= $n['is_pinned'] ? '<i class="fa-solid fa-thumbtack text-warning"></i>' : '—' ?></td>
                <td><?= $n['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Hidden</span>' ?></td>
                <td><small><?= date('d M Y', strtotime($n['created_at'])) ?></small></td>
                <td style="white-space:nowrap;">
                    <a href="?toggle=<?= $n['id'] ?>"
                       class="btn-sc <?= $n['is_active'] ? 'warning' : 'success' ?> xs">
                        <?= $n['is_active'] ? '<i class="fa-solid fa-eye-slash"></i> Hide' : '<i class="fa-solid fa-eye"></i> Show' ?>
                    </a>
                    <a href="?delete=<?= $n['id'] ?>"
                       class="btn-sc danger xs" data-confirm="Delete this notice permanently?">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-bullhorn me-2 text-primary"></i>Post New Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="Notice title" required>
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
                            <label class="form-label">Expiry Date (optional)</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Content *</label>
                            <textarea name="content" class="form-control" rows="5"
                                      placeholder="Write the notice content here..." required></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned">
                                <label class="form-check-label fw-600" for="isPinned">
                                    <i class="fa-solid fa-thumbtack me-1 text-warning"></i> Pin this notice to top
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_notice" class="btn-sc primary">
                        <i class="fa-solid fa-bullhorn"></i> Post Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

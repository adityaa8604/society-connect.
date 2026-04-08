<?php
/**
 * user/notices.php — View Society Notices
 */
require_once '../config/db.php';
requireLogin('resident');
$db = getDB();

// Increment view count if viewing a specific notice
if (isset($_GET['view'])) {
    $db->prepare("UPDATE notices SET views=views+1 WHERE id=?")->execute([(int)$_GET['view']]);
}

$filterCat = $_GET['cat'] ?? 'all';
$sql = "SELECT n.*, u.name as posted_by_name FROM notices n JOIN users u ON n.posted_by=u.id WHERE n.is_active=1";
if ($filterCat !== 'all') { $sql .= " AND n.category=?"; }
$sql .= " ORDER BY n.is_pinned DESC, n.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($filterCat !== 'all' ? [$filterCat] : []);
$notices = $stmt->fetchAll();

$categories = ['general','urgent','maintenance','event','finance','security'];

$pageTitle  = 'Notice Board';
$activePage = 'notices';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-800">Notice Board</h4>
</div>

<!-- Category Filter -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="?cat=all" class="btn-sc <?= $filterCat==='all'?'primary':'light' ?> sm">All</a>
    <?php foreach ($categories as $cat): ?>
    <a href="?cat=<?= $cat ?>" class="btn-sc <?= $filterCat===$cat?'primary':'light' ?> sm">
        <i class="fa-solid fa-<?= categoryIcon($cat) ?> me-1"></i> <?= ucfirst($cat) ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($notices)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fa-solid fa-bullhorn"></i></div>
    <h5>No Notices</h5><p>No notices available at the moment.</p>
</div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($notices as $n): ?>
    <div class="col-md-6 col-lg-4">
        <div class="notice-card <?= e($n['category']) ?>" style="height:100%;">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-<?= $n['category']==='urgent'?'danger':($n['category']==='event'?'warning':'primary') ?>">
                        <i class="fa-solid fa-<?= categoryIcon($n['category']) ?> me-1"></i>
                        <?= ucfirst($n['category']) ?>
                    </span>
                    <?php if ($n['is_pinned']): ?><span class="badge bg-secondary"><i class="fa-solid fa-thumbtack"></i> Pinned</span><?php endif; ?>
                </div>
                <span class="text-muted" style="font-size:11px;"><i class="fa-regular fa-eye me-1"></i><?= $n['views'] ?></span>
            </div>
            <h5 style="font-size:16px;"><?= e($n['title']) ?></h5>
            <p style="font-size:13.5px;"><?= nl2br(e($n['content'])) ?></p>
            <div class="notice-meta">
                <span><i class="fa-solid fa-user-circle"></i><?= e($n['posted_by_name']) ?></span>
                <span><i class="fa-regular fa-calendar"></i><?= date('d M Y',strtotime($n['created_at'])) ?></span>
                <?php if ($n['expiry_date']): ?>
                <span class="text-warning"><i class="fa-solid fa-clock"></i>Expires: <?= date('d M',strtotime($n['expiry_date'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

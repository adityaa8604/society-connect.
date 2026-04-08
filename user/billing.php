<?php
/**
 * user/billing.php — Resident Bills View
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

$resStmt = $db->prepare("SELECT r.*, f.flat_no FROM residents r JOIN flats f ON r.flat_id=f.id WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();
if (!$resident) { header("Location: ".BASE_URL."/user/index.php"); exit; }

$flatId = $resident['flat_id'];

// ---- MARK PAID (simulate online payment) ----
if (isset($_GET['pay'])) {
    $id = (int)$_GET['pay'];
    $db->prepare("UPDATE billing SET status='paid', paid_date=CURDATE(), payment_mode='Online' WHERE id=? AND flat_id=?")
       ->execute([$id, $flatId]);
    setFlash('success', 'Payment successful! Bill marked as paid.');
    header("Location: ".BASE_URL."/user/billing.php"); exit;
}

// Fetch bills
$filterStatus = $_GET['status'] ?? 'all';
$sql = "SELECT * FROM billing WHERE flat_id=?";
$params = [$flatId];
if ($filterStatus !== 'all') { $sql .= " AND status=?"; $params[] = $filterStatus; }
$sql .= " ORDER BY year DESC, month DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

// Summary
$summary = $db->prepare("SELECT
    SUM(amount) as total,
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid,
    SUM(CASE WHEN status IN('pending','overdue') THEN amount+penalty ELSE 0 END) as due
    FROM billing WHERE flat_id=?");
$summary->execute([$flatId]);
$summary = $summary->fetch();

$pageTitle  = 'My Bills';
$activePage = 'billing';
require_once '../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['total']??0) ?></div>
            <div class="stat-label">Total Billed</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['paid']??0) ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card danger">
            <div class="stat-icon-wrap danger"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['due']??0) ?></div>
            <div class="stat-label">Amount Due</div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach (['all'=>'All Bills','pending'=>'Pending','paid'=>'Paid','overdue'=>'Overdue'] as $val=>$label): ?>
    <a href="?status=<?= $val ?>"
       class="btn-sc <?= $filterStatus===$val?'primary':'light' ?> sm">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-receipt"></i> Bills for <?= e($resident['flat_no']) ?></div>
        <span class="badge bg-primary"><?= count($bills) ?> records</span>
    </div>
    <div class="table-responsive">
        <?php if (empty($bills)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-file-invoice"></i></div><h5>No Bills Found</h5></div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Bill Type</th><th>Month/Year</th><th>Amount</th><th>Penalty</th><th>Due Date</th><th>Paid On</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($bills as $i => $b): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td class="fw-600"><?= e($b['bill_type']) ?></td>
                <td><?= monthName($b['month']) ?> <?= $b['year'] ?></td>
                <td class="fw-700"><?= formatCurrency($b['amount']) ?></td>
                <td><?= $b['penalty']>0 ? '<span class="text-danger">+'.formatCurrency($b['penalty']).'</span>' : '—' ?></td>
                <td><?= $b['due_date'] ? date('d M Y',strtotime($b['due_date'])) : '—' ?></td>
                <td><?= $b['paid_date'] ? date('d M Y',strtotime($b['paid_date'])) : '—' ?></td>
                <td><?= statusBadge($b['status']) ?></td>
                <td>
                    <?php if (in_array($b['status'],['pending','overdue'])): ?>
                    <a href="?pay=<?= $b['id'] ?>" class="btn-sc success xs"
                       data-confirm="Pay <?= formatCurrency($b['amount']+$b['penalty']) ?> for <?= e($b['bill_type']) ?>?">
                        <i class="fa-solid fa-credit-card"></i> Pay Now
                    </a>
                    <?php elseif($b['status']==='paid'): ?>
                    <span class="text-success small fw-700"><i class="fa-solid fa-check me-1"></i>Paid</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

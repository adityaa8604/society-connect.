<?php
/**
 * admin/bookings.php — Facility Bookings Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- APPROVE/REJECT ----
if (isset($_GET['approve'])) {
    $db->prepare("UPDATE bookings SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    setFlash('success', 'Booking approved!');
    header("Location: " . BASE_URL . "/admin/bookings.php"); exit;
}
if (isset($_GET['reject'])) {
    $db->prepare("UPDATE bookings SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject']]);
    setFlash('success', 'Booking rejected.');
    header("Location: " . BASE_URL . "/admin/bookings.php"); exit;
}
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM bookings WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Booking deleted.');
    header("Location: " . BASE_URL . "/admin/bookings.php"); exit;
}

// ---- FETCH ----
$filterStatus = $_GET['status'] ?? 'all';
$sql = "SELECT bk.*, f.name as facility_name, fl.flat_no, u.name as resident_name
        FROM bookings bk
        JOIN facilities f ON bk.facility_id = f.id
        JOIN flats fl ON bk.flat_id = fl.id
        JOIN residents r ON bk.resident_id = r.id
        JOIN users u ON r.user_id = u.id";
if ($filterStatus !== 'all') $sql .= " WHERE bk.status='$filterStatus'";
$sql .= " ORDER BY bk.created_at DESC";
$bookings = $db->query($sql)->fetchAll();

$counts = $db->query("SELECT status, COUNT(*) cnt FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Bookings Management';
$activePage= 'bookings';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Facility Bookings</h4>
        <p class="text-muted mb-0">Approve, reject, and manage facility booking requests</p>
    </div>
</div>

<!-- Status Tabs -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php
    $tabs = ['all'=>['All','secondary'],'pending'=>['Pending','warning'],'approved'=>['Approved','success'],'rejected'=>['Rejected','danger'],'completed'=>['Completed','primary']];
    foreach ($tabs as $val => [$label,$color]): ?>
    <a href="?status=<?= $val ?>"
       class="badge bg-<?= $filterStatus===$val?$color:'light' ?> text-<?= $filterStatus===$val?'white':$color ?>"
       style="font-size:13px;padding:8px 16px;border:2px solid var(--bs-<?= $color ?>);text-decoration:none;">
        <?= $label ?>
        <?php if ($val !== 'all' && isset($counts[$val])): ?>(<?= $counts[$val] ?>)<?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-calendar-check"></i> Bookings (<?= count($bookings) ?>)</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($bookings)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-regular fa-calendar"></i></div><h5>No Bookings Found</h5></div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Facility</th><th>Resident</th><th>Date</th><th>Time</th><th>Guests</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $i => $b): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($b['facility_name']) ?></div>
                    <small class="text-muted"><?= e($b['purpose'] ?: '—') ?></small>
                </td>
                <td>
                    <div class="fw-600"><?= e($b['resident_name']) ?></div>
                    <span class="flat-pill"><?= e($b['flat_no']) ?></span>
                </td>
                <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                <td><?= $b['guests_count'] ?></td>
                <td class="fw-700"><?= formatCurrency($b['amount']) ?></td>
                <td><?= statusBadge($b['status']) ?></td>
                <td style="white-space:nowrap;">
                    <?php if ($b['status'] === 'pending'): ?>
                    <a href="?approve=<?= $b['id'] ?>&status=<?= $filterStatus ?>"
                       class="btn-sc success xs" data-confirm="Approve this booking?">
                        <i class="fa-solid fa-check"></i> Approve
                    </a>
                    <a href="?reject=<?= $b['id'] ?>&status=<?= $filterStatus ?>"
                       class="btn-sc danger xs" data-confirm="Reject this booking?">
                        <i class="fa-solid fa-xmark"></i> Reject
                    </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $b['id'] ?>&status=<?= $filterStatus ?>"
                       class="btn-sc light xs" data-confirm="Delete this booking record?">
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

<?php require_once '../includes/footer.php'; ?>

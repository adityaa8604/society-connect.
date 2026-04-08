<?php
/**
 * admin/billing.php — Billing Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD BILL ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bill'])) {
    $flatId   = (int)$_POST['flat_id'];
    $billType = trim($_POST['bill_type'] ?? 'Maintenance');
    $month    = (int)$_POST['month'];
    $year     = (int)$_POST['year'];
    $amount   = (float)$_POST['amount'];
    $dueDate  = $_POST['due_date'] ?? null;
    $notes    = trim($_POST['notes'] ?? '');

    // Get resident id
    $res = $db->prepare("SELECT id FROM residents WHERE flat_id=? LIMIT 1");
    $res->execute([$flatId]);
    $resId = $res->fetchColumn() ?: null;

    $ins = $db->prepare("INSERT INTO billing (flat_id,resident_id,bill_type,month,year,amount,due_date,notes) VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([$flatId,$resId,$billType,$month,$year,$amount,$dueDate ?: null,$notes]);
    logActivity(currentUser()['id'], "Bill added for flat $flatId", 'billing');
    setFlash('success', 'Bill added successfully!');
    header("Location: " . BASE_URL . "/admin/billing.php"); exit;
}

// ---- MARK PAID ----
if (isset($_GET['paid'])) {
    $id   = (int)$_GET['paid'];
    $mode = $_GET['mode'] ?? 'Cash';
    $db->prepare("UPDATE billing SET status='paid', paid_date=CURDATE(), payment_mode=? WHERE id=?")->execute([$mode,$id]);
    setFlash('success', 'Bill marked as paid!');
    header("Location: " . BASE_URL . "/admin/billing.php"); exit;
}

// ---- DELETE BILL ----
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM billing WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Bill deleted.');
    header("Location: " . BASE_URL . "/admin/billing.php"); exit;
}

// ---- GENERATE MONTHLY BILLS ----
if (isset($_POST['generate_bills'])) {
    $month  = (int)$_POST['gen_month'];
    $year   = (int)$_POST['gen_year'];
    $amount = (float)$_POST['gen_amount'];
    $due    = $_POST['gen_due'] ?? null;

    // Get all occupied flats with residents
    $flatsRes = $db->query("SELECT f.id as flat_id, r.id as res_id, f.type
                            FROM flats f JOIN residents r ON f.id=r.flat_id
                            WHERE f.status='occupied'")->fetchAll();
    $count = 0;
    foreach ($flatsRes as $fr) {
        // Check if bill already exists
        $chk = $db->prepare("SELECT id FROM billing WHERE flat_id=? AND month=? AND year=? AND bill_type='Maintenance' LIMIT 1");
        $chk->execute([$fr['flat_id'],$month,$year]);
        if (!$chk->fetch()) {
            $ins = $db->prepare("INSERT INTO billing (flat_id,resident_id,bill_type,month,year,amount,due_date) VALUES (?,?,'Maintenance',?,?,?,?)");
            $ins->execute([$fr['flat_id'],$fr['res_id'],$month,$year,$amount,$due ?: null]);
            $count++;
        }
    }
    setFlash('success', "Generated $count maintenance bills for " . monthName($month) . " $year.");
    header("Location: " . BASE_URL . "/admin/billing.php"); exit;
}

// ---- FILTER & FETCH ----
$filterStatus = $_GET['status'] ?? 'all';
$filterMonth  = (int)($_GET['month'] ?? date('n'));
$filterYear   = (int)($_GET['year']  ?? date('Y'));

$sql    = "SELECT b.*, f.flat_no, u.name as resident_name
           FROM billing b
           JOIN flats f ON b.flat_id = f.id
           LEFT JOIN residents r ON b.resident_id = r.id
           LEFT JOIN users u ON r.user_id = u.id";
$where  = ["b.month=?","b.year=?"];
$params = [$filterMonth, $filterYear];
if ($filterStatus !== 'all') { $where[] = "b.status=?"; $params[] = $filterStatus; }
$sql .= " WHERE " . implode(' AND ', $where) . " ORDER BY f.flat_no";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

// Summary
$summary = $db->prepare("SELECT
    SUM(amount) as total,
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid,
    SUM(CASE WHEN status IN('pending','overdue') THEN amount+penalty ELSE 0 END) as pending
    FROM billing WHERE month=? AND year=?");
$summary->execute([$filterMonth,$filterYear]);
$summary = $summary->fetch();

$allFlats = $db->query("SELECT id, flat_no, type FROM flats ORDER BY flat_no")->fetchAll();
$months   = range(1,12);
$years    = range(date('Y')-1, date('Y')+1);

$pageTitle = 'Billing Management';
$activePage= 'billing';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Billing Management</h4>
        <p class="text-muted mb-0">Manage maintenance bills and track payments</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-sc secondary sm" data-bs-toggle="modal" data-bs-target="#genBillsModal">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto Generate
        </button>
        <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addBillModal">
            <i class="fa-solid fa-plus"></i> Add Bill
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['total'] ?? 0) ?></div>
            <div class="stat-label">Total Billed (<?= monthName($filterMonth) ?>)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['paid'] ?? 0) ?></div>
            <div class="stat-label">Collected</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-num"><?= formatCurrency($summary['pending'] ?? 0) ?></div>
            <div class="stat-label">Pending / Overdue</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="sc-card mb-4">
    <div class="sc-card-body py-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1">Month</label>
                <select name="month" class="form-select form-select-sm" style="min-width:120px;">
                    <?php foreach ($months as $m): ?>
                    <option value="<?= $m ?>" <?= $m == $filterMonth ? 'selected' : '' ?>><?= monthName($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" <?= $filterStatus==='all'?'selected':'' ?>>All</option>
                    <option value="pending" <?= $filterStatus==='pending'?'selected':'' ?>>Pending</option>
                    <option value="paid"    <?= $filterStatus==='paid'?'selected':'' ?>>Paid</option>
                    <option value="overdue" <?= $filterStatus==='overdue'?'selected':'' ?>>Overdue</option>
                </select>
            </div>
            <button type="submit" class="btn-sc primary sm"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<!-- Bills Table -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-receipt"></i> Bills for <?= monthName($filterMonth) ?> <?= $filterYear ?></div>
        <span class="badge bg-primary"><?= count($bills) ?> records</span>
    </div>
    <div class="table-responsive">
        <?php if (empty($bills)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-file-invoice"></i></div>
            <h5>No Bills Found</h5>
            <p>Use "Auto Generate" to create maintenance bills for all residents.</p>
        </div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr>
                    <th>#</th><th>Flat</th><th>Resident</th><th>Bill Type</th>
                    <th>Amount</th><th>Penalty</th><th>Due Date</th><th>Status</th><th>Paid On</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bills as $i => $b): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="flat-pill"><?= e($b['flat_no']) ?></span></td>
                <td><?= e($b['resident_name'] ?: 'N/A') ?></td>
                <td><?= e($b['bill_type']) ?></td>
                <td class="fw-700"><?= formatCurrency($b['amount']) ?></td>
                <td><?= $b['penalty'] > 0 ? '<span class="text-danger fw-700">+'.formatCurrency($b['penalty']).'</span>' : '—' ?></td>
                <td><?= $b['due_date'] ? date('d M Y', strtotime($b['due_date'])) : '—' ?></td>
                <td><?= statusBadge($b['status']) ?></td>
                <td><?= $b['paid_date'] ? date('d M Y', strtotime($b['paid_date'])) : '—' ?></td>
                <td>
                    <?php if ($b['status'] !== 'paid' && $b['status'] !== 'waived'): ?>
                    <a href="?paid=<?= $b['id'] ?>&mode=Cash&<?= http_build_query(['month'=>$filterMonth,'year'=>$filterYear,'status'=>$filterStatus]) ?>"
                       class="btn-sc success xs"
                       data-confirm="Mark this bill as paid?">
                        <i class="fa-solid fa-check"></i> Paid
                    </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $b['id'] ?>&<?= http_build_query(['month'=>$filterMonth,'year'=>$filterYear,'status'=>$filterStatus]) ?>"
                       class="btn-sc danger xs"
                       data-confirm="Delete this bill permanently?">
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

<!-- Add Bill Modal -->
<div class="modal fade" id="addBillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-plus me-2 text-primary"></i>Add New Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Flat *</label>
                            <select name="flat_id" class="form-select" required>
                                <option value="">-- Select Flat --</option>
                                <?php foreach ($allFlats as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= e($f['flat_no']) ?> (<?= e($f['type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bill Type</label>
                            <select name="bill_type" class="form-select">
                                <?php foreach (['Maintenance','Water','Electricity','Parking','Club House','Other'] as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (₹) *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Month *</label>
                            <select name="month" class="form-select" required>
                                <?php foreach ($months as $m): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= monthName($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year *</label>
                            <select name="year" class="form-select" required>
                                <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_bill" class="btn-sc primary"><i class="fa-solid fa-plus"></i> Add Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Bills Modal -->
<div class="modal fade" id="genBillsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-wand-magic-sparkles me-2 text-secondary"></i>Auto-Generate Monthly Bills</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        This will generate maintenance bills for all occupied flats. Existing bills for the selected month will be skipped.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Month *</label>
                            <select name="gen_month" class="form-select" required>
                                <?php foreach ($months as $m): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= monthName($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year *</label>
                            <select name="gen_year" class="form-select" required>
                                <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount per Flat (₹) *</label>
                            <input type="number" name="gen_amount" class="form-control" value="3500" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="gen_due" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="generate_bills" class="btn-sc secondary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Bills
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
/**
 * admin/vendors.php — Vendor Management
 */
require_once '../config/db.php';
requireLogin('admin');
$db = getDB();

// ---- ADD VENDOR ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vendor'])) {
    $ins = $db->prepare("INSERT INTO vendors (name,service_type,contact_name,phone,email,address,contract_start,contract_end,status) VALUES (?,?,?,?,?,?,?,?,?)");
    $ins->execute([
        trim($_POST['name'] ?? ''),
        trim($_POST['service_type'] ?? ''),
        trim($_POST['contact_name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['address'] ?? ''),
        !empty($_POST['contract_start']) ? $_POST['contract_start'] : null,
        !empty($_POST['contract_end'])   ? $_POST['contract_end']   : null,
        $_POST['status'] ?? 'active',
    ]);
    setFlash('success', 'Vendor added!');
    header("Location: " . BASE_URL . "/admin/vendors.php"); exit;
}

// ---- TOGGLE STATUS ----
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $db->prepare("SELECT status FROM vendors WHERE id=?"); $cur->execute([$id]);
    $val = $cur->fetchColumn();
    $db->prepare("UPDATE vendors SET status=? WHERE id=?")->execute([$val==='active'?'inactive':'active', $id]);
    setFlash('success', 'Vendor status updated.');
    header("Location: " . BASE_URL . "/admin/vendors.php"); exit;
}

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM vendors WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Vendor removed.');
    header("Location: " . BASE_URL . "/admin/vendors.php"); exit;
}

$vendors = $db->query("SELECT * FROM vendors ORDER BY status, name")->fetchAll();
$activeCount   = count(array_filter($vendors, fn($v) => $v['status']==='active'));
$inactiveCount = count($vendors) - $activeCount;

$pageTitle = 'Vendor Management';
$activePage= 'vendors';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-800 mb-1">Vendor Management</h4>
        <p class="text-muted mb-0">Manage service vendors and contracts</p>
    </div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">
        <i class="fa-solid fa-plus"></i> Add Vendor
    </button>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon-wrap success"><i class="fa-solid fa-handshake"></i></div>
            <div class="stat-num"><?= $activeCount ?></div>
            <div class="stat-label">Active Vendors</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-icon-wrap warning"><i class="fa-solid fa-handshake-slash"></i></div>
            <div class="stat-num"><?= $inactiveCount ?></div>
            <div class="stat-label">Inactive Vendors</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon-wrap primary"><i class="fa-solid fa-building"></i></div>
            <div class="stat-num"><?= count($vendors) ?></div>
            <div class="stat-label">Total Vendors</div>
        </div>
    </div>
</div>

<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-handshake"></i> All Vendors</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($vendors)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-handshake"></i></div><h5>No Vendors</h5></div>
        <?php else: ?>
        <table class="table-sc">
            <thead>
                <tr><th>#</th><th>Vendor Name</th><th>Service</th><th>Contact</th><th>Phone</th><th>Contract Period</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($vendors as $i => $v): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="fw-700"><?= e($v['name']) ?></div>
                    <?php if ($v['email']): ?><small class="text-muted"><?= e($v['email']) ?></small><?php endif; ?>
                </td>
                <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?= e($v['service_type']) ?></span></td>
                <td><?= e($v['contact_name'] ?: '—') ?></td>
                <td><?= e($v['phone']) ?></td>
                <td>
                    <?php if ($v['contract_start'] && $v['contract_end']): ?>
                    <small><?= date('d M Y', strtotime($v['contract_start'])) ?></small>
                    <small class="text-muted"> → </small>
                    <small><?= date('d M Y', strtotime($v['contract_end'])) ?></small>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= statusBadge($v['status']) ?></td>
                <td style="white-space:nowrap;">
                    <a href="?toggle=<?= $v['id'] ?>"
                       class="btn-sc <?= $v['status']==='active'?'warning':'success' ?> xs">
                        <?= $v['status']==='active' ? '<i class="fa-solid fa-ban"></i> Deactivate' : '<i class="fa-solid fa-check"></i> Activate' ?>
                    </a>
                    <a href="?delete=<?= $v['id'] ?>" class="btn-sc danger xs"
                       data-confirm="Remove this vendor?">
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

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-plus me-2 text-primary"></i>Add New Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vendor Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="Company name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service Type *</label>
                            <select name="service_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach (['Housekeeping','Security','Electrical Repair','Plumbing','Lift Maintenance','Pest Control','Landscaping','CCTV','Internet','Water Tanker','Other'] as $svc): ?>
                                <option value="<?= $svc ?>"><?= $svc ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_name" class="form-control" placeholder="Full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 9800000000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="vendor@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract Start</label>
                            <input type="date" name="contract_start" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contract End</label>
                            <input type="date" name="contract_end" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Vendor address"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_vendor" class="btn-sc primary">
                        <i class="fa-solid fa-plus"></i> Add Vendor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

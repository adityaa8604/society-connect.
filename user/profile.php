<?php
/**
 * user/profile.php — Resident Profile
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

$resStmt = $db->prepare("SELECT r.*, f.flat_no, f.type as flat_type, f.block, f.area_sqft, f.floor
                          FROM residents r JOIN flats f ON r.flat_id=f.id
                          WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();

// ---- UPDATE PROFILE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name   = trim($_POST['name']   ?? '');
    $phone  = trim($_POST['phone']  ?? '');
    $curPwd = trim($_POST['current_password'] ?? '');
    $newPwd = trim($_POST['new_password']     ?? '');

    if (empty($name)) { setFlash('error', 'Name cannot be empty.'); }
    else {
        $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name,$phone,$user['id']]);
        $_SESSION['user_name'] = $name;

        if (!empty($curPwd) && !empty($newPwd)) {
            $u = $db->prepare("SELECT password FROM users WHERE id=?"); $u->execute([$user['id']]);
            $hash = $u->fetchColumn();
            if (password_verify($curPwd, $hash)) {
                if (strlen($newPwd) >= 6) {
                    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($newPwd, PASSWORD_BCRYPT),$user['id']]);
                    setFlash('success', 'Profile and password updated!');
                } else {
                    setFlash('error', 'New password must be at least 6 characters.');
                }
            } else {
                setFlash('error', 'Current password is incorrect.');
            }
        } else {
            setFlash('success', 'Profile updated successfully!');
        }
    }
    header("Location: ".BASE_URL."/user/profile.php"); exit;
}

// Get full user data
$userFull = $db->prepare("SELECT * FROM users WHERE id=?"); $userFull->execute([$user['id']]);
$userFull  = $userFull->fetch();

$pageTitle  = 'My Profile';
$activePage = 'profile';
require_once '../includes/header.php';
?>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-md-4">
        <div class="sc-card text-center">
            <div class="sc-card-body" style="padding:36px 24px;">
                <div class="user-avatar-sm mx-auto mb-3"
                     style="width:80px;height:80px;font-size:32px;background:linear-gradient(135deg,var(--primary),var(--secondary));">
                    <?= strtoupper(substr($userFull['name'],0,1)) ?>
                </div>
                <h4 class="fw-800 mb-1"><?= e($userFull['name']) ?></h4>
                <p class="text-muted mb-2"><?= e($userFull['email']) ?></p>
                <span class="badge bg-primary px-3 py-2"><?= ucfirst($userFull['role']) ?></span>
                <?php if ($resident): ?>
                <div class="mt-4 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid var(--border);">
                    <div class="fw-700 mb-2 text-muted" style="font-size:12px;text-transform:uppercase;">Flat Details</div>
                    <h3 class="fw-800 text-primary mb-1"><?= e($resident['flat_no']) ?></h3>
                    <p class="text-muted mb-1"><?= e($resident['flat_type']) ?> · Block <?= e($resident['block']) ?></p>
                    <p class="text-muted mb-1"><?= $resident['floor'] == 0 ? 'Ground Floor' : 'Floor '.$resident['floor'] ?></p>
                    <?php if ($resident['area_sqft']): ?>
                    <p class="text-muted mb-0"><?= $resident['area_sqft'] ?> sqft</p>
                    <?php endif; ?>
                    <div class="mt-2"><?= statusBadge($resident['resident_type']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resident Details -->
        <?php if ($resident): ?>
        <div class="sc-card mt-4">
            <div class="sc-card-header"><div class="sc-card-title"><i class="fa-solid fa-house"></i> Residence Info</div></div>
            <div class="sc-card-body">
                <div class="mb-3">
                    <label class="form-label">Family Members</label>
                    <p class="fw-700 mb-0"><?= $resident['members_count'] ?></p>
                </div>
                <?php if ($resident['vehicles']): ?>
                <div class="mb-3">
                    <label class="form-label">Vehicles</label>
                    <p class="fw-700 mb-0"><?= e($resident['vehicles']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($resident['move_in_date']): ?>
                <div class="mb-3">
                    <label class="form-label">Move-In Date</label>
                    <p class="fw-700 mb-0"><?= date('d M Y',strtotime($resident['move_in_date'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($resident['emergency_contact']): ?>
                <div>
                    <label class="form-label">Emergency Contact</label>
                    <p class="fw-700 mb-0"><?= e($resident['emergency_contact']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile -->
    <div class="col-md-8">
        <div class="sc-card">
            <div class="sc-card-header">
                <div class="sc-card-title"><i class="fa-solid fa-pen"></i> Edit Profile</div>
            </div>
            <div class="sc-card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= e($userFull['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= e($userFull['email']) ?>" readonly style="background:#F8FAFC;">
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($userFull['phone']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Login</label>
                            <input type="text" class="form-control" value="<?= $userFull['last_login'] ? date('d M Y, h:i A',strtotime($userFull['last_login'])) : 'N/A' ?>" readonly style="background:#F8FAFC;">
                        </div>
                    </div>

                    <hr class="divider">
                    <h6 class="fw-700 mb-3">Change Password <small class="text-muted fw-400">(leave blank to keep current)</small></h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Current password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="update_profile" class="btn-sc primary lg">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

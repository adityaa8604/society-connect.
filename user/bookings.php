<?php
/**
 * user/bookings.php — Facility Booking
 */
require_once '../config/db.php';
requireLogin('resident');
$db   = getDB();
$user = currentUser();

$resStmt = $db->prepare("SELECT r.*, f.flat_no FROM residents r JOIN flats f ON r.flat_id=f.id WHERE r.user_id=? LIMIT 1");
$resStmt->execute([$user['id']]);
$resident = $resStmt->fetch();
if (!$resident) { header("Location: ".BASE_URL."/user/index.php"); exit; }

// ---- ADD BOOKING ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $facilityId  = (int)$_POST['facility_id'];
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime   = $_POST['start_time']  ?? '';
    $endTime     = $_POST['end_time']    ?? '';
    $guests      = (int)($_POST['guests_count'] ?? 0);
    $purpose     = trim($_POST['purpose'] ?? '');

    if (empty($bookingDate) || empty($startTime) || empty($endTime)) {
        setFlash('error', 'Please fill all required fields.');
    } elseif ($endTime <= $startTime) {
        setFlash('error', 'End time must be after start time.');
    } else {
        // Check for conflicts
        $conflict = $db->prepare("SELECT id FROM bookings WHERE facility_id=? AND booking_date=?
                                  AND status IN('pending','approved')
                                  AND NOT (end_time <= ? OR start_time >= ?) LIMIT 1");
        $conflict->execute([$facilityId,$bookingDate,$startTime,$endTime]);
        if ($conflict->fetch()) {
            setFlash('error', 'This time slot is already booked. Please choose a different time.');
        } else {
            // Get facility charges
            $fac = $db->prepare("SELECT charges FROM facilities WHERE id=?");
            $fac->execute([$facilityId]);
            $charges = $fac->fetchColumn() ?: 0;

            $ins = $db->prepare("INSERT INTO bookings (facility_id,resident_id,flat_id,booking_date,start_time,end_time,guests_count,purpose,amount) VALUES (?,?,?,?,?,?,?,?,?)");
            $ins->execute([$facilityId,$resident['id'],$resident['flat_id'],$bookingDate,$startTime,$endTime,$guests,$purpose,$charges]);
            logActivity($user['id'], "Facility booking requested", 'bookings');
            setFlash('success', 'Booking request submitted! Awaiting admin approval.');
        }
    }
    header("Location: ".BASE_URL."/user/bookings.php"); exit;
}

// ---- CANCEL BOOKING ----
if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    $db->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND resident_id=?")->execute([$id,$resident['id']]);
    setFlash('success', 'Booking cancelled.');
    header("Location: ".BASE_URL."/user/bookings.php"); exit;
}

// Facilities & My bookings
$facilities = $db->query("SELECT * FROM facilities WHERE status='available' ORDER BY name")->fetchAll();
$myBookings = $db->prepare("SELECT bk.*, f.name as facility_name
                             FROM bookings bk JOIN facilities f ON bk.facility_id=f.id
                             WHERE bk.resident_id=? ORDER BY bk.created_at DESC");
$myBookings->execute([$resident['id']]);
$myBookings = $myBookings->fetchAll();

$showAdd = isset($_GET['action']) && $_GET['action'] === 'add';

$pageTitle  = 'Facility Booking';
$activePage = 'bookings';
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div><h4 class="fw-800 mb-1">Facility Booking</h4></div>
    <button class="btn-sc primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
        <i class="fa-solid fa-calendar-plus"></i> New Booking
    </button>
</div>

<!-- Facilities Grid -->
<h5 class="fw-700 mb-3 text-muted">Available Facilities</h5>
<div class="row g-3 mb-5">
    <?php foreach ($facilities as $f): ?>
    <div class="col-md-4 col-sm-6">
        <div class="facility-card">
            <div class="facility-img">
                <?php if ($f['image_url']): ?>
                <img src="<?= e($f['image_url']) ?>" alt="<?= e($f['name']) ?>" loading="lazy">
                <?php else: ?>
                <i class="fa-solid fa-building"></i>
                <?php endif; ?>
                <div style="position:absolute;top:10px;right:10px;">
                    <?= statusBadge($f['status']) ?>
                </div>
            </div>
            <div class="facility-body">
                <div class="facility-name"><?= e($f['name']) ?></div>
                <div class="facility-desc"><?= e($f['description']) ?></div>
                <div class="facility-meta">
                    <div class="facility-charge"><?= $f['charges'] > 0 ? formatCurrency($f['charges']) . '/booking' : 'Free' ?></div>
                    <button class="btn-sc primary sm" onclick="selectFacility(<?= $f['id'] ?>, '<?= e($f['name']) ?>')"
                            data-bs-toggle="modal" data-bs-target="#bookingModal">
                        <i class="fa-solid fa-calendar-plus"></i> Book
                    </button>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A',strtotime($f['open_time'])) ?> – <?= date('h:i A',strtotime($f['close_time'])) ?></small>
                    <small class="text-muted ms-3"><i class="fa-solid fa-users me-1"></i>Capacity: <?= $f['capacity'] ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- My Bookings -->
<div class="sc-card">
    <div class="sc-card-header">
        <div class="sc-card-title"><i class="fa-solid fa-calendar-check"></i> My Bookings</div>
    </div>
    <div class="table-responsive">
        <?php if (empty($myBookings)): ?>
        <div class="empty-state"><div class="empty-icon"><i class="fa-regular fa-calendar"></i></div><h5>No Bookings</h5><p>Book a facility to see it here.</p></div>
        <?php else: ?>
        <table class="table-sc">
            <thead><tr><th>#</th><th>Facility</th><th>Date</th><th>Time</th><th>Purpose</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($myBookings as $i => $b): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td class="fw-700"><?= e($b['facility_name']) ?></td>
                <td><?= date('d M Y',strtotime($b['booking_date'])) ?></td>
                <td><?= date('h:i A',strtotime($b['start_time'])) ?> – <?= date('h:i A',strtotime($b['end_time'])) ?></td>
                <td><?= e($b['purpose']?:'—') ?></td>
                <td><?= formatCurrency($b['amount']) ?></td>
                <td><?= statusBadge($b['status']) ?></td>
                <td>
                    <?php if ($b['status'] === 'pending'): ?>
                    <a href="?cancel=<?= $b['id'] ?>" class="btn-sc danger xs"
                       data-confirm="Cancel this booking?">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade <?= $showAdd?'show':'' ?>" id="bookingModal" tabindex="-1" <?= $showAdd?'style="display:block;"':'' ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-800"><i class="fa-solid fa-calendar-plus me-2 text-primary"></i>Book a Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" class="form-select" id="facilitySelect" required>
                                <option value="">-- Select Facility --</option>
                                <?php foreach ($facilities as $f): ?>
                                <option value="<?= $f['id'] ?>" data-charge="<?= $f['charges'] ?>"><?= e($f['name']) ?> (<?= $f['charges']>0?formatCurrency($f['charges']):'Free' ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Booking Date *</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" onchange="validateBookingTime()" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Expected Guests</label>
                            <input type="number" name="guests_count" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Birthday party, Meeting...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sc light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_booking" class="btn-sc primary">
                        <i class="fa-solid fa-calendar-check"></i> Submit Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($showAdd): ?><div class="modal-backdrop fade show"></div><?php endif; ?>

<script>
function selectFacility(id, name) {
    document.getElementById('facilitySelect').value = id;
}
</script>

<?php require_once '../includes/footer.php'; ?>

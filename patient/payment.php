<?php
require_once '../config/database.php';
require_once '../config/payment.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

if (!isLoggedIn('patient')) {
    redirect('/login.php');
}

if (!isset($_SESSION['pending_booking'])) {
    setFlashMessage('danger', "No booking in progress.", 'danger');
    redirect('dashboard.php');
}

$booking = $_SESSION['pending_booking'];
$doctor_id = $booking['doctor_id'];
$slot_id = $booking['slot_id'];
$notes = $booking['notes'];

// Fetch Details for Display
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor_name = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE id = ?");
    $stmt->execute([$slot_id]);
    $slot_time = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$amount = CONSULTATION_FEE;

// Handle "Other" Payment Method (Manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_manual') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $method = $_POST['method']; // Cash or UPI
        $ref_id = trim($_POST['reference_id']);

        // Finalize Booking
        finalizeBooking($pdo, $doctor_id, $slot_id, $notes, 'pending', $method, $ref_id, $amount);
    }
}

// Handle Razorpay Success (Mock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    // In real life, verify signature here.
    $payment_id = $_POST['razorpay_payment_id'];
    finalizeBooking($pdo, $doctor_id, $slot_id, $notes, 'paid', 'razorpay', $payment_id, $amount);
}

function finalizeBooking($pdo, $doctor_id, $slot_id, $notes, $pay_status, $pay_method, $txn_id, $amount) {
    try {
        $pdo->beginTransaction();

        // Check slot again (Concurrency)
        $check = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE id = ? AND is_booked = 0 FOR UPDATE");
        $check->execute([$slot_id]);
        $slot = $check->fetch();

        if (!$slot) {
            $pdo->rollBack();
            setFlashMessage('danger', "Slot taken during payment. Please try again.", 'danger');
            unset($_SESSION['pending_booking']);
            redirect('dashboard.php');
        }

        // Mark Slot
        $pdo->prepare("UPDATE appointment_slots SET is_booked = 1 WHERE id = ?")->execute([$slot_id]);

        // Insert Appointment
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, notes, slot_id, payment_status, payment_method, transaction_id, amount) VALUES (?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $doctor_id, $slot['slot_datetime'], $notes, $slot_id, $pay_status, $pay_method, $txn_id, $amount]);

        $pdo->commit();

        // Notifications
        $pat_email = $_SESSION['user_email'] ?? ''; // Assuming set in login, else fetch
        // Fetch user data if session doesn't have it
        if (empty($pat_email)) {
             $u = $pdo->prepare("SELECT email, phone FROM users WHERE id = ?");
             $u->execute([$_SESSION['user_id']]);
             $udata = $u->fetch();
             $pat_email = $udata['email'];
             $pat_phone = $udata['phone'];
        }

        sendEmail($pat_email, "Appointment Confirmed", "Your appointment is booked. Payment: " . ucfirst($pay_status));

        unset($_SESSION['pending_booking']);
        setFlashMessage('success', "Appointment booked successfully!", 'success');
        redirect('dashboard.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('danger', "Error: " . $e->getMessage(), 'danger');
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Complete Payment</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Consultation Summary</h5>
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor_name); ?></p>
                    <p><strong>Time:</strong> <?php echo date('M d, Y g:i A', strtotime($slot_time)); ?></p>
                    <p><strong>Fee:</strong> ₹<?php echo number_format($amount, 2); ?></p>
                    <hr>

                    <!-- Razorpay Button -->
                    <h6 class="mt-4">Pay Online</h6>
                    <button id="rzp-button1" class="btn btn-success w-100">Pay with Razorpay</button>

                    <form name='razorpayform' action="payment.php" method="POST">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                    </form>

                    <hr>

                    <!-- Manual Payment -->
                    <h6 class="mt-4">Pay by Other Method</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="pay_manual">
                        <div class="mb-3">
                            <select name="method" class="form-select" required>
                                <option value="Cash">Cash at Clinic</option>
                                <option value="UPI">Direct UPI Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="reference_id" class="form-control" placeholder="Transaction Ref ID (Optional)">
                        </div>
                        <button type="submit" class="btn btn-outline-secondary w-100">Confirm Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Razorpay Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo RAZORPAY_KEY_ID; ?>",
    "amount": "<?php echo $amount * 100; ?>", // Amount in paise
    "currency": "INR",
    "name": "Ayurveda Clinic",
    "description": "Consultation Fee",
    "handler": function (response){
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.razorpayform.submit();
    },
    "prefill": {
        "name": "<?php echo $_SESSION['user_name']; ?>",
        "email": "patient@example.com"
    },
    "theme": {
        "color": "#3399cc"
    }
};
var rzp1 = new Razorpay(options);
document.getElementById('rzp-button1').onclick = function(e){
    rzp1.open();
    e.preventDefault();
}
</script>

<?php require_once '../includes/footer.php'; ?>

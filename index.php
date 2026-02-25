<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<div class="p-5 mb-4 bg-light rounded-3 text-center">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold text-primary">Welcome to Our Clinic</h1>
        <p class="col-md-8 fs-4 mx-auto">Providing quality healthcare for you and your family. Book appointments online, manage your health records, and connect with our expert doctors.</p>

        <?php if (isLoggedIn()): ?>
            <?php
                $dashboardLink = '#';
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'superadmin') $dashboardLink = '/superadmin/dashboard.php';
                    elseif ($_SESSION['role'] === 'admin') $dashboardLink = '/admin/dashboard.php';
                    elseif ($_SESSION['role'] === 'doctor') $dashboardLink = '/doctor/dashboard.php';
                    else $dashboardLink = '/patient/dashboard.php';
                }
            ?>
            <a href="<?php echo $dashboardLink; ?>" class="btn btn-primary btn-lg" type="button">Go to Dashboard</a>
        <?php else: ?>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="/login.php" class="btn btn-primary btn-lg px-4 gap-3">Login</a>
                <a href="/register.php" class="btn btn-outline-secondary btn-lg px-4">Register as Patient</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="row align-items-md-stretch">
        <div class="col-md-6">
            <div class="h-100 p-5 text-white bg-dark rounded-3">
                <h2>For Patients</h2>
                <p>Register online to book appointments with our specialists. View your appointment history and status updates in real-time.</p>
                <a href="/register.php" class="btn btn-outline-light" type="button">Get Started</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="h-100 p-5 bg-light border rounded-3">
                <h2>For Doctors</h2>
                <p>Manage your schedule efficiently. View upcoming appointments and update patient status seamlessly through our dedicated portal.</p>
                <a href="/login.php" class="btn btn-outline-secondary" type="button">Doctor Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

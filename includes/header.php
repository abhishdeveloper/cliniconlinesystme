<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Define the base URL if needed, or rely on relative paths
// For simplicity in shared hosting, relative paths or a defined constant is best.
// Let's assume relative paths from the root for now.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/index.php"><i class="fas fa-clinic-medical"></i> Clinic System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                        <li class="nav-item"><a class="nav-link" href="/doctor/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/chat.php">Messages</a></li>
                    <?php elseif ($_SESSION['role'] === 'patient'): ?>
                        <li class="nav-item"><a class="nav-link" href="/patient/dashboard.php">My Appointments</a></li>
                        <li class="nav-item"><a class="nav-link" href="/chat.php">Messages</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link btn btn-danger text-white ms-2" href="/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Flash Messages Display -->
    <?php
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $key => $msg) {
             echo "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                    {$msg['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
             unset($_SESSION['flash'][$key]);
        }
    }
    ?>

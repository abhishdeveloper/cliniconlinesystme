<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a superadmin
if (!isLoggedIn('superadmin')) {
    setFlashMessage('danger', 'Access denied.', 'danger');
    redirect('/login.php');
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Super Admin Dashboard</h2>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-primary mb-3 h-100">
                    <div class="card-header">Manage Users</div>
                    <div class="card-body">
                        <h5 class="card-title">User Management</h5>
                        <p class="card-text">View, edit, and delete users (Admins, Doctors, Patients).</p>
                        <a href="manage_users.php" class="btn btn-light">Go to Users</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success mb-3 h-100">
                    <div class="card-header">Add New User</div>
                    <div class="card-body">
                        <h5 class="card-title">Create Account</h5>
                        <p class="card-text">Add new Admins or Doctors to the system.</p>
                        <a href="add_user.php" class="btn btn-light">Add User</a>
                    </div>
                </div>
            </div>

             <div class="col-md-4 mb-3">
                <div class="card text-white bg-info mb-3 h-100">
                    <div class="card-header">System Overview</div>
                    <div class="card-body">
                        <h5 class="card-title">Reports</h5>
                        <p class="card-text">View system-wide reports and statistics.</p>
                        <button class="btn btn-light disabled">Coming Soon</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

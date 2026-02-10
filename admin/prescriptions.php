<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin
if (!isLoggedIn('admin')) {
    setFlashMessage('danger', "Access Denied. Admins only.", 'danger');
    redirect('/login.php');
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>All Prescriptions</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-file-prescription"></i> Prescription History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT p.id, p.created_at, p.diagnosis,
                                       u_pat.name AS patient_name, u_pat.email AS patient_email,
                                       u_doc.name AS doctor_name
                                FROM prescriptions p
                                JOIN users u_pat ON p.patient_id = u_pat.id
                                JOIN users u_doc ON p.doctor_id = u_doc.id
                                ORDER BY p.created_at DESC
                            ");

                            $prescriptions = $stmt->fetchAll();

                            if (count($prescriptions) > 0) {
                                foreach ($prescriptions as $rx) {
                                    echo "<tr>";
                                    echo "<td>#" . $rx['id'] . "</td>";
                                    echo "<td>" . date('M d, Y', strtotime($rx['created_at'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($rx['patient_name']) . "<br><small class='text-muted'>" . htmlspecialchars($rx['patient_email']) . "</small></td>";
                                    echo "<td>Dr. " . htmlspecialchars($rx['doctor_name']) . "</td>";
                                    // Truncate diagnosis
                                    $diag = htmlspecialchars($rx['diagnosis']);
                                    if (strlen($diag) > 50) $diag = substr($diag, 0, 50) . '...';
                                    echo "<td>" . $diag . "</td>";
                                    echo "<td>
                                            <a href='../prescription_view.php?id=" . $rx['id'] . "' class='btn btn-sm btn-outline-primary' target='_blank'><i class='fas fa-eye'></i> View</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No prescriptions found.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='6' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid prescription ID.");
}

$prescription_id = $_GET['id'];

// Fetch Prescription Data with Doctor and Patient Info
$stmt = $pdo->prepare("
    SELECT p.*,
           u_pat.name AS patient_name, u_pat.email AS patient_email, u_pat.phone AS patient_phone,
           u_doc.name AS doctor_name, u_doc.email AS doctor_email,
           a.appointment_date
    FROM prescriptions p
    JOIN users u_pat ON p.patient_id = u_pat.id
    JOIN users u_doc ON p.doctor_id = u_doc.id
    JOIN appointments a ON p.appointment_id = a.id
    WHERE p.id = ?
");
$stmt->execute([$prescription_id]);
$rx = $stmt->fetch();

if (!$rx) {
    die("Prescription not found.");
}

// Access Control
if ($_SESSION['role'] === 'patient' && $_SESSION['user_id'] != $rx['patient_id']) {
    die("Access Denied.");
}
if ($_SESSION['role'] === 'doctor' && $_SESSION['user_id'] != $rx['doctor_id']) {
    die("Access Denied.");
}
// Admin can view all

$medicines = json_decode($rx['medicines_json'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #<?php echo $rx['id']; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- html2pdf.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .rx-header { border-bottom: 2px solid #28a745; padding-bottom: 20px; margin-bottom: 20px; }
        .rx-logo { font-size: 2.5rem; color: #28a745; }
        .rx-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .med-table th { background-color: #e9ecef; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-end mb-3 no-print">
        <button id="downloadPdf" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</button>
        <button onclick="window.print()" class="btn btn-secondary ms-2"><i class="fas fa-print"></i> Print</button>
        <a href="javascript:history.back()" class="btn btn-outline-dark ms-2">Back</a>
    </div>

    <!-- Printable Area -->
    <div class="card shadow p-5" id="prescriptionContent">
        <!-- Header -->
        <div class="rx-header row">
            <div class="col-md-8">
                <i class="fas fa-leaf rx-logo"></i>
                <h2 class="d-inline-block ms-3">Ayurveda Clinic</h2>
                <p class="text-muted ms-5 ps-2">Holistic Healing & Wellness Center</p>
            </div>
            <div class="col-md-4 text-end">
                <h5>Dr. <?php echo htmlspecialchars($rx['doctor_name']); ?></h5>
                <p class="mb-0">Ayurvedic Practitioner</p>
                <small>Email: <?php echo htmlspecialchars($rx['doctor_email']); ?></small>
            </div>
        </div>

        <!-- Patient Details -->
        <div class="rx-details row mb-4">
            <div class="col-md-6">
                <strong>Patient Name:</strong> <?php echo htmlspecialchars($rx['patient_name']); ?><br>
                <strong>Date:</strong> <?php echo date('d M Y', strtotime($rx['appointment_date'])); ?><br>
                <strong>Rx ID:</strong> #<?php echo $rx['id']; ?>
            </div>
            <div class="col-md-6 text-end">
                <strong>Diagnosis / Prakriti Analysis:</strong><br>
                <?php echo nl2br(htmlspecialchars($rx['diagnosis'])); ?>
            </div>
        </div>

        <!-- Ayurvedic Assessment -->
        <?php if (!empty($rx['prakruti']) || !empty($rx['vikruti']) || !empty($rx['agni'])): ?>
        <div class="row mb-4 p-3 bg-white border rounded">
            <div class="col-12">
                <h5 class="text-success border-bottom pb-2 mb-3">Ayurvedic Assessment</h5>
                <div class="row">
                    <?php if (!empty($rx['prakruti'])): ?>
                    <div class="col-md-4 mb-2">
                        <strong>Prakruti:</strong> <span class="text-dark"><?php echo htmlspecialchars($rx['prakruti']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($rx['vikruti'])): ?>
                    <div class="col-md-4 mb-2">
                        <strong>Vikruti:</strong> <span class="text-dark"><?php echo htmlspecialchars($rx['vikruti']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($rx['agni'])): ?>
                    <div class="col-md-4 mb-2">
                        <strong>Agni:</strong> <span class="text-dark"><?php echo htmlspecialchars($rx['agni']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Medicines Table -->
        <h4 class="mb-3 text-success"><i class="fas fa-mortar-pestle"></i> Prescribed Medicines</h4>
        <table class="table table-bordered med-table mb-4">
            <thead>
                <tr>
                    <th>Medicine Name</th>
                    <th>Type</th>
                    <th>Dosage / Anupana (Vehicle)</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($medicines)): ?>
                    <?php foreach ($medicines as $med): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($med['type']); ?></td>
                            <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($med['duration']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No medicines listed.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Notes / Lifestyle Advice -->
        <?php if (!empty($rx['notes'])): ?>
            <div class="alert alert-light border border-success">
                <h5 class="text-success"><i class="fas fa-apple-alt"></i> Pathya / Apathya (Lifestyle Advice)</h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($rx['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="row mt-5 pt-4 border-top">
            <div class="col-md-6">
                <p class="text-muted small">This is a computer-generated prescription.</p>
            </div>
            <div class="col-md-6 text-end">
                <br>
                <p class="border-top d-inline-block pt-1 px-5">Doctor's Signature</p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('downloadPdf').addEventListener('click', function() {
    const element = document.getElementById('prescriptionContent');
    const opt = {
        margin:       0.5,
        filename:     'Prescription_<?php echo $rx['id']; ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
});
</script>

</body>
</html>

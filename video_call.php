<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('danger', 'You must be logged in to access this page.');
    redirect('login.php');
}

$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    setFlashMessage('danger', 'Invalid appointment ID.');
    redirect('index.php');
}

try {
    // Fetch appointment details
    $stmt = $pdo->prepare("
        SELECT a.*, p.name as patient_name, d.name as doctor_name
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN users d ON a.doctor_id = d.id
        WHERE a.id = ?
    ");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        setFlashMessage('danger', 'Appointment not found.');
        redirect('index.php');
    }

    // Access Control
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Allow Admin to view any call (optional, but useful for debugging/oversight)
    // Or strictly limit to patient/doctor.
    $is_authorized = ($user_id == $appointment['patient_id']) || ($user_id == $appointment['doctor_id']);

    if (!$is_authorized) {
        setFlashMessage('danger', 'Access Denied. You are not a participant in this appointment.');
        redirect('index.php');
    }

    if ($appointment['status'] !== 'confirmed' && $appointment['status'] !== 'completed') {
        setFlashMessage('warning', 'This appointment is not confirmed.');
        redirect('index.php');
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// All checks passed
$pageTitle = "Video Consultation";
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-video"></i> Consultation: <?php echo htmlspecialchars($appointment['patient_name']); ?> with Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                    </h4>
                    <a href="<?php echo $role === 'doctor' ? 'doctor/dashboard.php' : 'patient/dashboard.php'; ?>" class="btn btn-light btn-sm">Return to Dashboard</a>
                </div>
                <div class="card-body p-0" style="height: 600px;">
                    <div id="meet" style="width: 100%; height: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src='https://meet.jit.si/external_api.js'></script>
<script>
    const domain = 'meet.jit.si';
    const options = {
        roomName: 'ClinicApp_Appt_<?php echo $appointment['id']; ?>',
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#meet'),
        userInfo: {
            displayName: '<?php echo htmlspecialchars($_SESSION['user_name']); ?>'
        },
        configOverwrite: {
            startWithAudioMuted: false,
            disableDeepLinking: true
        },
        interfaceConfigOverwrite: {
            SHOW_JITSI_WATERMARK: false,
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                'security'
            ]
        }
    };
    const api = new JitsiMeetExternalAPI(domain, options);

    // Optional: Handle hangup
    api.addEventListeners({
        videoConferenceLeft: function () {
            window.location.href = '<?php echo $role === 'doctor' ? 'doctor/dashboard.php' : 'patient/dashboard.php'; ?>';
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>

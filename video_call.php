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
    $user_name = $_SESSION['user_name'];

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

// Handle File Upload via AJAX (will be called by JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_doc') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (isset($_FILES['file'])) {
        $res = uploadFile($_FILES['file'], 'uploads/reports/');
        if ($res['success']) {
            $webPath = str_replace('../', '', $res['path']);
            // Use existing patient_reports or create new table? Using patient_reports for now
            $stmt = $pdo->prepare("INSERT INTO patient_reports (patient_id, appointment_id, title, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$appointment['patient_id'], $appointment_id, $_FILES['file']['name'], $webPath, $user_id]);
            echo json_encode(['status' => 'success', 'message' => 'Uploaded']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $res['message']]);
        }
    }
    exit;
}

// Handle Caption Save via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_caption') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf_token)) {
        $text = trim($_POST['text']);
        if (!empty($text)) {
            // Need table call_captions
            $stmt = $pdo->prepare("INSERT INTO call_captions (appointment_id, user_id, caption_text) VALUES (?, ?, ?)");
            $stmt->execute([$appointment_id, $user_id, $text]);
        }
    }
    exit;
}

// Handle Get Data via AJAX (Captions & Docs)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'poll_data') {
    // Get Docs
    $stmt = $pdo->prepare("SELECT * FROM patient_reports WHERE appointment_id = ? ORDER BY created_at DESC");
    $stmt->execute([$appointment_id]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Captions (last 10 seconds)
    // Check if table exists first? Assuming schema is updated
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.name FROM call_captions c JOIN users u ON c.user_id = u.id WHERE appointment_id = ? AND c.created_at > (NOW() - INTERVAL 10 SECOND) ORDER BY c.created_at ASC");
        $stmt->execute([$appointment_id]);
        $captions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $captions = [];
    }

    echo json_encode(['docs' => $docs, 'captions' => $captions]);
    exit;
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
                    <div class="split-container" style="display: flex; height: 100%;">
                        <!-- Jitsi Video Area -->
                        <div id="meet" style="width: 70%; height: 100%;"></div>

                        <!-- Side Panel -->
                        <div class="side-panel" style="width: 30%; display: flex; flex-direction: column; border-left: 1px solid #ccc; background: #f8f9fa;">
                            <!-- Real-time Captions -->
                            <div class="p-2 bg-dark text-white d-flex justify-content-between">
                                <span><i class="fas fa-closed-captioning"></i> Live Captions</span>
                                <small id="mic-status">Mic Off</small>
                            </div>
                            <div class="captions-area" id="captions-box" style="height: 40%; overflow-y: auto; padding: 15px; background: #fff; border-bottom: 1px solid #ddd;">
                                <p class="text-muted text-center mt-4">Start speaking to see captions...</p>
                            </div>
                            <div class="p-2 border-top">
                                <button id="toggle-speech" class="btn btn-outline-primary btn-sm w-100 mb-2">Enable Auto-Transcription</button>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="manual-caption" class="form-control" placeholder="Type if mic fails...">
                                    <button class="btn btn-secondary" onclick="sendManualCaption()"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </div>

                            <!-- Document Sharing -->
                            <div class="p-2 bg-secondary text-white">
                                <i class="fas fa-file-upload"></i> Shared Documents
                            </div>
                            <div class="docs-area" style="height: 60%; padding: 15px; overflow-y: auto;">
                                <div class="file-drop-zone" onclick="document.getElementById('file-input').click()" style="border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer; background: #fff; margin-bottom: 15px;">
                                    <p class="mb-0"><i class="fas fa-cloud-upload-alt fa-2x"></i><br>Click to Upload Document</p>
                                    <input type="file" id="file-input" style="display: none;" onchange="uploadFile(this)">
                                </div>

                                <ul class="list-group" id="docs-list">
                                    <!-- Docs loaded via AJAX -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src='https://meet.jit.si/external_api.js'></script>
<script>
    const domain = 'meet.jit.si';
    const csrfToken = "<?php echo generateCsrfToken(); ?>";
    const userName = "<?php echo htmlspecialchars($user_name); ?>";
    const roomName = 'ClinicApp_Appt_<?php echo $appointment['id']; ?>';

    const options = {
        roomName: roomName,
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#meet'),
        userInfo: {
            displayName: userName
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

    // --- Web Speech API (Captions) ---
    let recognition;
    let isTranscribing = false;

    if ('webkitSpeechRecognition' in window) {
        recognition = new webkitSpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-US'; // Target English

        recognition.onstart = function() {
            document.getElementById('mic-status').innerText = "Listening...";
            document.getElementById('mic-status').style.color = "#0f0";
            document.getElementById('toggle-speech').innerText = "Disable Transcription";
            document.getElementById('toggle-speech').classList.replace('btn-outline-primary', 'btn-danger');
        };

        recognition.onend = function() {
            if (isTranscribing) recognition.start(); // Restart if intended to be on
            else {
                 document.getElementById('mic-status').innerText = "Mic Off";
                 document.getElementById('mic-status').style.color = "#000"; // Changed from #fff to black for visibility
                 document.getElementById('toggle-speech').innerText = "Enable Auto-Transcription";
                 document.getElementById('toggle-speech').classList.replace('btn-danger', 'btn-outline-primary');
            }
        };

        recognition.onresult = function(event) {
            let finalTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                }
            }
            if (finalTranscript) {
                sendCaption(finalTranscript);
            }
        };
    } else {
        alert("Web Speech API not supported in this browser. Please use Chrome/Edge.");
    }

    document.getElementById('toggle-speech').onclick = function() {
        if (!recognition) return;
        if (isTranscribing) {
            isTranscribing = false;
            recognition.stop();
        } else {
            isTranscribing = true;
            recognition.start();
        }
    };

    // Manual Fallback
    function sendManualCaption() {
        const input = document.getElementById('manual-caption');
        const text = input.value.trim();
        if (text) {
            sendCaption(text);
            input.value = '';
        }
    }

    // Allow Enter key
    document.getElementById('manual-caption').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendManualCaption();
        }
    });

    // --- AJAX Helper Functions ---

    function sendCaption(text) {
        const formData = new FormData();
        formData.append('action', 'save_caption');
        formData.append('text', text);
        formData.append('csrf_token', csrfToken);

        fetch(window.location.href, { method: 'POST', body: formData });
        // Optimistic UI update
        appendCaption("You", text);
    }

    function appendCaption(user, text) {
        const box = document.getElementById('captions-box');
        const div = document.createElement('div');
        div.className = 'caption-line';
        div.style.marginBottom = '5px';
        div.style.fontSize = '1.1em';

        // Securely create elements to prevent XSS
        const userSpan = document.createElement('span');
        userSpan.className = 'caption-user';
        userSpan.style.fontWeight = 'bold';
        userSpan.style.color = '#007bff';
        userSpan.textContent = user + ': ';

        const textNode = document.createTextNode(text);

        div.appendChild(userSpan);
        div.appendChild(textNode);

        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function uploadFile(input) {
        if (input.files.length === 0) return;

        const formData = new FormData();
        formData.append('action', 'upload_doc');
        formData.append('file', input.files[0]);
        formData.append('csrf_token', csrfToken);

        // Show loading
        const list = document.getElementById('docs-list');
        const li = document.createElement('li');
        li.className = 'list-group-item text-muted';
        li.innerText = 'Uploading...';
        list.prepend(li);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Poll immediately will refresh list
                    pollData();
                } else {
                    alert("Upload failed: " + data.message);
                    li.remove();
                }
            })
            .catch(err => {
                console.error(err);
                li.remove();
            });
    }

    // --- Polling (Every 3 seconds) ---
    let lastCaptionId = 0; // Simple optimization to avoid duplicates if ID was sent

    function pollData() {
        // Construct clean polling URL (strip hash/query accumulation)
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'poll_data');

        fetch(url)
            .then(res => res.json())
            .then(data => {
                // Update Docs
                const docList = document.getElementById('docs-list');
                const existingDocs = docList.innerHTML; // Simple check to avoid full redraw flicker if unnecessary
                // Rebuild is easiest
                let newHtml = '';
                if (data.docs.length > 0) {
                    data.docs.forEach(doc => {
                        // Securely escape title
                        const div = document.createElement('div');
                        div.textContent = doc.title;
                        const safeTitle = div.innerHTML;

                        newHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 70%;">
                                    <i class="fas fa-file"></i> ${safeTitle}
                                </div>
                                <a href="${doc.file_path}" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-download"></i></a>
                            </li>`;
                    });
                } else {
                    newHtml = '<li class="list-group-item text-muted text-center">No docs yet</li>';
                }

                if (docList.innerHTML !== newHtml) {
                    docList.innerHTML = newHtml;
                }

                // Update Captions
                if (data.captions.length > 0) {
                     data.captions.forEach(cap => {
                         // Only show captions from *others* that are newer than last seen ID
                         if (cap.name !== userName) {
                             if (cap.id > lastCaptionId) {
                                 appendCaption(cap.name, cap.caption_text);
                                 lastCaptionId = cap.id;
                             }
                         }
                     });
                }
            })
            .catch(err => console.error("Poll Error:", err));
    }

    setInterval(pollData, 3000);
    pollData(); // Initial load

</script>

<?php require_once 'includes/footer.php'; ?>

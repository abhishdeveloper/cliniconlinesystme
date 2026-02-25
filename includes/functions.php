<?php
if (session_status() === PHP_SESSION_NONE) {
    // Prevent session fixation
    ini_set('session.use_strict_mode', 1);

    // Determine if connection is secure
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    if (!$secure && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $secure = true;
    }

    // Set secure cookie params
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/',
        'domain' => '', // Current domain
        'secure' => $secure,
        'httponly' => true, // Prevent JS access
        'samesite' => 'Strict' // Prevent CSRF
    ]);

    session_start();
}

/**
 * Escapes output to prevent XSS.
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if a user is logged in.
 * Optionally checks for a specific role.
 * @param string|null $role
 * @return bool
 */
function isLoggedIn($role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($role && $_SESSION['role'] !== $role) {
        return false;
    }
    return true;
}

/**
 * Redirects to a specific URL.
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Sets a flash message.
 * @param string $key
 * @param string $message
 * @param string $type (success, danger, warning, info)
 */
function setFlashMessage($key, $message, $type = 'info') {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Gets and clears a flash message.
 * @param string $key
 * @return string|null
 */
function getFlashMessage($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                    {$msg['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return null;
}

/**
 * Generates a CSRF token.
 * If one already exists in the session, it returns it.
 * Otherwise, it generates a new one.
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token.
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Aliases for compatibility with other branches/features
function generateCsrfToken() {
    return generate_csrf_token();
}

function verifyCsrfToken($token) {
    return verify_csrf_token($token);
}

/**
 * Uploads a file with basic validation.
 * @param array $file The $_FILES['name'] array.
 * @param string $targetDir Relative path to upload directory.
 * @return array ['success' => bool, 'message' => string, 'path' => string|null]
 */
function uploadFile($file, $targetDir = '../uploads/reports/') {
    // Ensure directory exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = basename($file["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate File Type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => "Only JPG, PNG, PDF, DOC files are allowed."];
    }

    // Validate File Size (Max 5MB)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => "File is too large. Max 5MB."];
    }

    // Generate Unique Name
    $newFileName = uniqid('report_', true) . '.' . $fileType;
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Return path relative to web root if possible, or just the relative path stored
        // Storing "uploads/reports/filename.ext" is usually best.
        // Since function call might use '../uploads', we need to clean it for DB if needed.
        // Let's assume we store the path provided + filename.
        return ['success' => true, 'message' => "File uploaded.", 'path' => $targetFile];
    } else {
        return ['success' => false, 'message' => "Error moving file."];
    }
}

/**
 * Cancels an appointment.
 * @param PDO $pdo
 * @param int $appt_id
 * @param int $patient_id
 * @param string $reason
 * @return array ['success' => bool, 'message' => string]
 */
function cancelAppointment($pdo, $appt_id, $patient_id, $reason) {
    try {
        // Start transaction if not already in one
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) {
            $pdo->beginTransaction();
        }

        // 1. Fetch Appointment & Verify Ownership & Status
        $stmt = $pdo->prepare("SELECT a.*, u.name as doctor_name, u.email as doctor_email, u.phone as doctor_phone
                               FROM appointments a
                               JOIN users u ON a.doctor_id = u.id
                               WHERE a.id = ? AND a.patient_id = ?");
        $stmt->execute([$appt_id, $patient_id]);
        $appt = $stmt->fetch();

        if (!$appt) {
            throw new Exception("Appointment not found or access denied.");
        }

        if ($appt['status'] === 'cancelled' || $appt['status'] === 'completed') {
             throw new Exception("Appointment is already " . $appt['status'] . ".");
        }

        // 2. Check Time Restriction (2 hours)
        $appt_time = strtotime($appt['appointment_date']);
        $current_time = time();
        $diff_hours = ($appt_time - $current_time) / 3600;

        if ($diff_hours < 2) {
             throw new Exception("Appointments cannot be cancelled within 2 hours of the scheduled time.");
        }

        // 3. Update Appointment Status
        $new_notes = $appt['notes'] . " [Cancelled by Patient: $reason]";
        $update = $pdo->prepare("UPDATE appointments SET status = 'cancelled', notes = ? WHERE id = ?");
        $update->execute([$new_notes, $appt_id]);

        // 4. Free up the Slot
        if ($appt['slot_id']) {
            $free_slot = $pdo->prepare("UPDATE appointment_slots SET is_booked = 0 WHERE id = ?");
            $free_slot->execute([$appt['slot_id']]);
        }

        // Commit if we started the transaction
        if (!$inTransaction) {
            $pdo->commit();
        }

        // 5. Send Notifications
        // Notify Patient
        $pat_stmt = $pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
        $pat_stmt->execute([$patient_id]);
        $patient = $pat_stmt->fetch();

        $formatted_date = date('F j, Y g:i A', $appt_time);

        $msg_pat = "Your appointment with Dr. {$appt['doctor_name']} on $formatted_date has been cancelled.";
        $pat_phone = $patient['phone'] ?? '+15550000000';

        // Ensure sendSMS/sendEmail are available or include notifications.php
        if (function_exists('sendSMS')) {
             sendSMS($pat_phone, $msg_pat);
        }
        if (function_exists('sendEmail')) {
            sendEmail($patient['email'], "Appointment Cancelled", $msg_pat);
        }

        // Notify Doctor
        $msg_doc = "Appointment cancelled by patient: {$patient['name']} on $formatted_date. Reason: $reason";
        $doc_phone = $appt['doctor_phone'] ?? '+15550000000';

        if (function_exists('sendSMS')) {
            sendSMS($doc_phone, $msg_doc);
        }
        if (function_exists('sendEmail')) {
            sendEmail($appt['doctor_email'], "Appointment Cancelled", $msg_doc);
        }

        return ['success' => true, 'message' => "Appointment cancelled successfully."];

    } catch (Exception $e) {
        if (isset($inTransaction) && !$inTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>

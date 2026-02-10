<?php
/**
 * Notification Helper Functions
 * Handles sending Emails and SMS.
 * Supports a 'Mock Mode' for testing without real credentials.
 */

// Configuration (In a real app, move these to a config file)
define('NOTIFY_MOCK_MODE', true); // Set to FALSE to enable real sending
define('SMTP_FROM_EMAIL', 'no-reply@clinic.com');
define('SMS_API_KEY', 'YOUR_SMS_API_KEY'); // e.g., Twilio or TextLocal
define('SMS_SENDER_ID', 'CLINIC');

/**
 * Log notification to file (for testing/debugging)
 */
function logNotification($type, $to, $subject, $body) {
    $logFile = __DIR__ . '/../uploads/notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$type] TO: $to | SUB: $subject | BODY: $body" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Send Email
 * Uses PHP mail() by default.
 */
function sendEmail($to, $subject, $message) {
    if (NOTIFY_MOCK_MODE) {
        logNotification('EMAIL', $to, $subject, $message);
        return true;
    }

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SMTP_FROM_EMAIL . "\r\n";

    // Basic mail function (Works on most shared hosting)
    return mail($to, $subject, $message, $headers);
}

/**
 * Send SMS
 * Placeholder for Twilio/Generic HTTP API
 */
function sendSMS($phone, $message) {
    if (empty($phone)) return false;

    if (NOTIFY_MOCK_MODE) {
        logNotification('SMS', $phone, 'N/A', $message);
        return true;
    }

    // Example: Twilio Integration (Commented out)
    /*
    $sid = 'YOUR_TWILIO_SID';
    $token = 'YOUR_TWILIO_TOKEN';
    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    $data = [
        'From' => '+15550000000',
        'To' => $phone,
        'Body' => $message
    ];
    // Use curl to send...
    */

    return true; // Assume success for now
}
?>

<?php
// Notification System (Lightweight Native PHP)

/**
 * Send SMS using Twilio API (Mock/Production Switch)
 * @param string $to Phone number (e.g., +15551234567)
 * @param string $body Message content
 * @return bool True on success, False on failure
 */
function sendSMS($to, $body) {
    // Configuration - Set these in production or use environment variables
    $sid = 'YOUR_TWILIO_SID';
    $token = 'YOUR_TWILIO_TOKEN';
    $from = 'YOUR_TWILIO_PHONE';

    // Basic Header Injection Protection for SMS (less critical but good practice)
    if (preg_match("/[\r\n]/", $to) || preg_match("/[\r\n]/", $from)) {
        return false;
    }

    // In development/test mode, just log it.
    if ($sid === 'YOUR_TWILIO_SID') {
        error_log("[MOCK SMS] To: $to | Body: $body");
        return true;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    $data = [
        'From' => $from,
        'To' => $to,
        'Body' => $body
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code >= 200 && $http_code < 300);
}

/**
 * Send Email using Simple SMTP Implementation
 * This avoids heavy libraries like PHPMailer for lightweight hosting.
 * @param string $to Recipient email
 * @param string $subject Subject line
 * @param string $message Email body
 * @return bool True on success
 */
function sendEmail($to, $subject, $message) {
    // Configuration
    $smtp_host = 'smtp.example.com';
    $smtp_port = 587;
    $smtp_user = 'user@example.com';
    $smtp_pass = 'password';
    $from_email = 'noreply@clinic.com';
    $from_name = 'Clinic System';

    // Header Injection Protection
    if (preg_match("/[\r\n]/", $to) || preg_match("/[\r\n]/", $subject)) {
        error_log("Potential Header Injection detected in email to: $to");
        return false;
    }

    // In development, fall back to PHP mail() or log
    if ($smtp_host === 'smtp.example.com') {
        error_log("[MOCK EMAIL] To: $to | Subject: $subject | Body: $message");
        return true;
        // return mail($to, $subject, $message, "From: $from_email"); // Use standard mail() if configured
    }

    try {
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15);
        if (!$socket) return false;

        server_parse($socket, '220');
        fwrite($socket, "EHLO $smtp_host\r\n");
        server_parse($socket, '250');
        fwrite($socket, "STARTTLS\r\n");
        server_parse($socket, '220');
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fwrite($socket, "EHLO $smtp_host\r\n");
        server_parse($socket, '250');
        fwrite($socket, "AUTH LOGIN\r\n");
        server_parse($socket, '334');
        fwrite($socket, base64_encode($smtp_user) . "\r\n");
        server_parse($socket, '334');
        fwrite($socket, base64_encode($smtp_pass) . "\r\n");
        server_parse($socket, '235');

        fwrite($socket, "MAIL FROM: <$from_email>\r\n");
        server_parse($socket, '250');
        fwrite($socket, "RCPT TO: <$to>\r\n");
        server_parse($socket, '250');
        fwrite($socket, "DATA\r\n");
        server_parse($socket, '354');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: $from_name <$from_email>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
        server_parse($socket, '250');
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper to send command and check response for SMTP
 * @param resource $socket
 * @param string $response Expected response code
 * @return bool
 */
function server_parse($socket, $response) {
    $server_response = '';
    while (substr($server_response, 3, 1) != ' ') {
        if (!($server_response = fgets($socket, 256))) return false;
    }
    if (!(substr($server_response, 0, 3) == $response)) return false;
    return true;
}
?>

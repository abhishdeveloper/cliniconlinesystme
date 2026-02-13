<?php
// Notification System (Lightweight Native PHP)

// Interfaces
interface SMSProvider {
    public function send($to, $body);
}

interface EmailProvider {
    public function send($to, $subject, $message);
}

// SMS Implementations
class MockSMSProvider implements SMSProvider {
    public function send($to, $body) {
        error_log("[MOCK SMS] To: $to | Body: $body");
        return true;
    }
}

class TwilioSMSProvider implements SMSProvider {
    private $sid;
    private $token;
    private $from;

    public function __construct($sid, $token, $from) {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
    }

    public function send($to, $body) {
        // Basic Header Injection Protection
        if (preg_match("/[\r\n]/", $to) || preg_match("/[\r\n]/", $this->from)) {
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
        $data = [
            'From' => $this->from,
            'To' => $to,
            'Body' => $body
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // As per original code
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->sid}:{$this->token}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_code >= 200 && $http_code < 300);
    }
}

// Email Implementations
class MockEmailProvider implements EmailProvider {
    public function send($to, $subject, $message) {
        // Header Injection Protection (Consistent with original)
        if (preg_match("/[\r\n]/", $to) || preg_match("/[\r\n]/", $subject)) {
            error_log("Potential Header Injection detected in email to: $to");
            return false;
        }
        error_log("[MOCK EMAIL] To: $to | Subject: $subject | Body: $message");
        return true;
    }
}

class SMTPEmailProvider implements EmailProvider {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $fromEmail;
    private $fromName;

    public function __construct($host, $port, $user, $pass, $fromEmail, $fromName) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    private function serverParse($socket, $response) {
        $server_response = '';
        while (substr($server_response, 3, 1) != ' ') {
            if (!($server_response = fgets($socket, 256))) return false;
        }
        if (!(substr($server_response, 0, 3) == $response)) return false;
        return true;
    }

    public function send($to, $subject, $message) {
        // Header Injection Protection
        if (preg_match("/[\r\n]/", $to) || preg_match("/[\r\n]/", $subject)) {
            error_log("Potential Header Injection detected in email to: $to");
            return false;
        }

        try {
            $socket = fsockopen($this->host, $this->port, $errno, $errstr, 15);
            if (!$socket) return false;

            if (!$this->serverParse($socket, '220')) return false;
            fwrite($socket, "EHLO {$this->host}\r\n");
            if (!$this->serverParse($socket, '250')) return false;
            fwrite($socket, "STARTTLS\r\n");
            if (!$this->serverParse($socket, '220')) return false;
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($socket, "EHLO {$this->host}\r\n");
            if (!$this->serverParse($socket, '250')) return false;
            fwrite($socket, "AUTH LOGIN\r\n");
            if (!$this->serverParse($socket, '334')) return false;
            fwrite($socket, base64_encode($this->user) . "\r\n");
            if (!$this->serverParse($socket, '334')) return false;
            fwrite($socket, base64_encode($this->pass) . "\r\n");
            if (!$this->serverParse($socket, '235')) return false;

            fwrite($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
            if (!$this->serverParse($socket, '250')) return false;
            fwrite($socket, "RCPT TO: <$to>\r\n");
            if (!$this->serverParse($socket, '250')) return false;
            fwrite($socket, "DATA\r\n");
            if (!$this->serverParse($socket, '354')) return false;

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";

            fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
            if (!$this->serverParse($socket, '250')) return false;
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
}

// Global Functions

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

    // In development/test mode, use Mock Provider
    if ($sid === 'YOUR_TWILIO_SID') {
        $provider = new MockSMSProvider();
    } else {
        $provider = new TwilioSMSProvider($sid, $token, $from);
    }

    return $provider->send($to, $body);
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

    // In development, use Mock Provider
    if ($smtp_host === 'smtp.example.com') {
        $provider = new MockEmailProvider();
    } else {
        $provider = new SMTPEmailProvider($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name);
    }

    return $provider->send($to, $subject, $message);
}
?>

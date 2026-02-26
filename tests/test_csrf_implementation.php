<?php
// Test Script for CSRF Implementation
$url = 'http://localhost:8000/login.php';
$cookie_file = __DIR__ . '/cookie.txt';

// Helper function to make requests
function make_request($url, $method = 'GET', $post_fields = [], $cookie_file = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects automatically to check headers
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output

    if ($cookie_file) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $http_code, 'response' => $response];
}

// 1. Initial GET to set session cookie and get token
echo "1. GET request to login.php...\n";
if (file_exists($cookie_file)) unlink($cookie_file);
$res = make_request($url, 'GET', [], $cookie_file);
echo "Response Code: " . $res['code'] . "\n";

// Extract CSRF Token using Regex
$token = '';
if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $res['response'], $matches)) {
    $token = $matches[1];
    echo "CSRF Token Found: " . $token . "\n";
} else {
    echo "FAIL: CSRF Token NOT Found in login page.\n";
    exit(1);
}

// 2. Attempt Login WITHOUT CSRF Token
echo "\n2. POST request (Login) WITHOUT CSRF token...\n";
$post_data_no_token = [
    'email' => 'admin@clinic.com',
    'password' => 'admin123'
];
$res_fail = make_request($url, 'POST', $post_data_no_token, $cookie_file);
echo "Response Code: " . $res_fail['code'] . "\n";

if (strpos($res_fail['response'], 'CSRF validation failed') !== false) {
    echo "PASS: Request rejected with 'CSRF validation failed' message.\n";
} else {
    echo "FAIL: Request was NOT rejected as expected.\n";
    // echo $res_fail['response'];
}

// 3. Attempt Login WITH CSRF Token
echo "\n3. POST request (Login) WITH CSRF token...\n";
$post_data_with_token = [
    'email' => 'admin@clinic.com',
    'password' => 'admin123',
    'csrf_token' => $token
];
$res_success = make_request($url, 'POST', $post_data_with_token, $cookie_file);
echo "Response Code: " . $res_success['code'] . "\n";

if ($res_success['code'] == 302) {
    if (preg_match('/Location: (.*)/i', $res_success['response'], $matches)) {
        echo "PASS: Login SUCCEEDED (Redirected to " . trim($matches[1]) . ").\n";
    } else {
         echo "PASS: Login SUCCEEDED (Redirected).\n";
    }
} else {
    echo "FAIL: Login failed with valid token.\n";
    echo "Response Body Snippet: " . substr($res_success['response'], 0, 500) . "\n";
}
?>

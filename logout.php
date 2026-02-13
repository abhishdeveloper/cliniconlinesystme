<?php
require_once 'includes/functions.php';

// Unset all session values
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');
?>

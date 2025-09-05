<?php
session_start();

// 1. Clear all session variables
$_SESSION = [];
session_unset();
session_destroy();

// 2. Remove Remember Me cookies
if (isset($_COOKIE['admin_id'])) {
    setcookie("admin_id", "", time() - 3600, "/");
}
if (isset($_COOKIE['admin_token'])) {
    setcookie("admin_token", "", time() - 3600, "/");
}

// 3. Redirect to login page
header("Location: admin_login.php");
exit;
?>

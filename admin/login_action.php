<?php
session_start();
require "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password   = $_POST['password'];
    $remember   = isset($_POST['remember']);

    // Check if identifier matches email or phone
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email=? OR phone=? LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        // Store session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];

        // Handle Remember Me (set cookie for 7 days)
        if ($remember) {
            setcookie("admin_id", $admin['id'], time() + (7 * 24 * 60 * 60), "/");
            setcookie("admin_token", hash('sha256', $admin['email'] . $admin['phone']), time() + (7 * 24 * 60 * 60), "/");
        }

        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('❌ Invalid login credentials'); window.location='admin_login.php';</script>";
    }
}
?>

<?php
session_start();
require "../db.php";


// ✅ Auto-login with Remember Me cookies
if (!isset($_SESSION['admin_id']) && isset($_COOKIE['admin_id']) && isset($_COOKIE['admin_token'])) {
    $admin_id = $_COOKIE['admin_id'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin) {
        $tokenCheck = hash('sha256', $admin['email'] . $admin['phone']);
        if ($tokenCheck === $_COOKIE['admin_token']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
        }
    }
}

// 🚨 If still not logged in → redirect to login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    if (!empty($password)) {
        // Hash password if updated
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE admins SET name=?, email=?, phone=?, password=? WHERE id=?");
        $update->bind_param("ssssi", $name, $email, $phone, $hashed, $admin_id);
    } else {
        // Update without changing password
        $update = $conn->prepare("UPDATE admins SET name=?, email=?, phone=? WHERE id=?");
        $update->bind_param("sssi", $name, $email, $phone, $admin_id);
    }

    if ($update->execute()) {
        $_SESSION['success'] = "✅ Profile updated successfully!";
        header("Location: profile.php");
        exit;
    } else {
        $error = "❌ Error updating profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - Glossera</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <?php include 'header.php'; ?>
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold text-red-600 mb-4">👤 Admin Profile</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700">Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required
                    class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required
                    class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-gray-700">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>" required
                    class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-gray-700">New Password (leave blank to keep current)</label>
                <input type="password" name="password" placeholder="Enter new password"
                    class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-gray-700">Account Created</label>
                <input type="text" value="<?= $admin['created_at'] ?>" disabled
                    class="w-full border p-2 rounded bg-gray-100">
            </div>

            <button type="submit"
                class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700 transition">
                Update Profile
            </button>
        </form>
    </div>
</body>
</html>

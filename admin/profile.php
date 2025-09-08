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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Glossera Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Standard Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../fav_icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../fav_icon/favicon-16x16.png">
    <!-- Apple Touch Icon (for iOS devices) -->
    <link rel="apple-touch-icon" href="../fav_icon/apple-touch-icon.png">
    <!-- Web Manifest (for PWA or mobile install) -->
    <link rel="manifest" href="../fav_icon/site.webmanifest">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(102, 126, 234, 0.2);
        }
        .profile-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'header.php'; ?>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header Section -->
        <div class="mb-8 fade-in-up">
            <div class="gradient-bg rounded-2xl p-6 sm:p-8 text-white shadow-2xl">
                <div class="flex flex-col sm:flex-row items-center">
                    <div class="profile-avatar w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold mb-4 sm:mb-0 sm:mr-6 shadow-lg">
                        <?= strtoupper(substr($admin['name'], 0, 2)) ?>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-3xl sm:text-4xl font-bold mb-2 flex items-center justify-center sm:justify-start">
                            <i class="fas fa-user-cog mr-3"></i>Admin Profile
                        </h1>
                        <p class="text-blue-100 text-lg">Manage your account settings and preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Profile Information Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover fade-in-up" style="animation-delay: 0.2s;">
                        <div class="text-center">
                            <div class="profile-avatar w-24 h-24 rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-4 shadow-lg">
                                <?= strtoupper(substr($admin['name'], 0, 2)) ?>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($admin['name']) ?></h3>
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($admin['email']) ?></p>
                            
                            <div class="space-y-3 text-sm text-gray-600">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-phone w-4 text-green-500 mr-3"></i>
                                    <span><?= htmlspecialchars($admin['phone']) ?></span>
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-calendar-alt w-4 text-blue-500 mr-3"></i>
                                    <span>Joined <?= date('M Y', strtotime($admin['created_at'])) ?></span>
                                </div>
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-shield-alt w-4 text-purple-500 mr-3"></i>
                                    <span>Administrator</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats Card -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover mt-6 fade-in-up" style="animation-delay: 0.4s;">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-blue-500"></i>Account Stats
                        </h4>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Account Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-circle text-xs mr-1"></i>Active
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Last Login</span>
                                <span class="text-sm text-gray-900">Today</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Session</span>
                                <span class="text-sm text-gray-900">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 card-hover fade-in-up" style="animation-delay: 0.3s;">
                        <div class="flex items-center mb-6">
                            <i class="fas fa-edit text-2xl text-blue-600 mr-3"></i>
                            <h2 class="text-2xl font-bold text-gray-900">Update Profile</h2>
                        </div>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-400 p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                                    <span class="text-green-700 font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-400 p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                                    <span class="text-red-700 font-medium"><?= $error; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-blue-500"></i>Full Name
                                    </label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus transition-all duration-200">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-green-500"></i>Email Address
                                    </label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus transition-all duration-200">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-purple-500"></i>Phone Number
                                </label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus transition-all duration-200">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-orange-500"></i>New Password
                                </label>
                                <input type="password" name="password" placeholder="Leave blank to keep current password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus transition-all duration-200">
                                <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-calendar-plus mr-2 text-gray-400"></i>Account Created
                                </label>
                                <input type="text" value="<?= date('F j, Y \a\t g:i A', strtotime($admin['created_at'])) ?>" disabled
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed">
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 pt-6">
                                <button type="submit"
                                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center font-semibold">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                                <button type="button" onclick="location.reload()"
                                    class="flex-1 bg-gray-100 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center font-semibold">
                                    <i class="fas fa-undo mr-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 card-hover mt-6 fade-in-up" style="animation-delay: 0.5s;">
                        <div class="flex items-center mb-6">
                            <i class="fas fa-shield-alt text-2xl text-green-600 mr-3"></i>
                            <h3 class="text-xl font-bold text-gray-900">Security Settings</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border-l-4 border-green-400">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-green-800">Two-Factor Auth</h4>
                                        <p class="text-sm text-green-600">Enhanced security</p>
                                    </div>
                                    <div class="text-green-600">
                                        <i class="fas fa-toggle-off text-2xl cursor-pointer hover:text-green-700"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border-l-4 border-blue-400">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-blue-800">Session Timeout</h4>
                                        <p class="text-sm text-blue-600">Auto logout: 30 min</p>
                                    </div>
                                    <div class="text-blue-600">
                                        <i class="fas fa-clock text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add fade-in animation for elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in-up');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
            });
            
            // Trigger animations
            setTimeout(() => {
                elements.forEach((element) => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                });
            }, 100);
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            if (password && password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
            }
        });

        // Add smooth hover effects for cards
        document.querySelectorAll('.card-hover').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 20px 40px -6px rgba(0, 0, 0, 0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
            });
        });
    </script>
</body>
</html>
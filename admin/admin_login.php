<?php
session_start();
require "../db.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glossera Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header Section -->
        <div class="text-center fade-in-up">
            <div class="gradient-bg w-16 h-16 mx-auto rounded-xl flex items-center justify-center shadow-lg mb-4">
                <i class="fas fa-car text-2xl text-white"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Glossera Admin</h2>
            <p class="mt-2 text-sm text-gray-600">Sign in to your administrator account</p>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 card-hover fade-in-up" style="animation-delay: 0.2s;">
            <form action="login_action.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-blue-500"></i>Email or Phone
                    </label>
                    <input type="text" name="identifier" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                        placeholder="Enter your email or phone number">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-purple-500"></i>Password
                    </label>
                    <input type="password" name="password" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 input-focus"
                        placeholder="Enter your password">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" 
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                    <label for="remember" class="ml-2 text-sm text-gray-700 font-medium">Remember Me</label>
                </div>

                <button type="submit" 
                    class="w-full gradient-bg text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-3 gap-4 fade-in-up" style="animation-delay: 0.4s;">
            <div class="bg-white p-4 rounded-xl shadow-lg text-center card-hover">
                <i class="fas fa-shield-alt text-2xl text-green-500 mb-2"></i>
                <p class="text-xs text-gray-600">Secure</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-lg text-center card-hover">
                <i class="fas fa-clock text-2xl text-blue-500 mb-2"></i>
                <p class="text-xs text-gray-600">24/7 Access</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-lg text-center card-hover">
                <i class="fas fa-users-cog text-2xl text-purple-500 mb-2"></i>
                <p class="text-xs text-gray-600">Admin Panel</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center fade-in-up" style="animation-delay: 0.6s;">
            <p class="text-xs text-gray-500">
                © 2025 Glossera. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        // Add fade-in animation for elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in-up');
            elements.forEach((element) => {
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
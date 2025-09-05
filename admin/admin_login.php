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
  <title>Glossera Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-sm">
    <h2 class="text-2xl font-bold text-center text-red-600 mb-6">Glossera Admin</h2>
    <form action="login_action.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-gray-700">Email or Phone</label>
        <input type="text" name="identifier" required 
          class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
      </div>
      <div>
        <label class="block text-gray-700">Password</label>
        <input type="password" name="password" required 
          class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
      </div>

      <div class="flex items-center">
        <input type="checkbox" name="remember" id="remember" class="mr-2">
        <label for="remember" class="text-gray-700">Remember Me</label>
      </div>

      <button type="submit" 
        class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
        Login
      </button>
    </form>
  </div>
</body>
</html>

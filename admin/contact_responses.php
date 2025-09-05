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

// 🚨 Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ✅ Handle Status Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['contact_id'], $_POST['status'])) {
    $id = intval($_POST['contact_id']);
    $status = ($_POST['status'] === "read") ? "read" : "unread";

    $stmt = $conn->prepare("UPDATE contact_inquiries SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    header("Location: contact_responses.php"); // refresh page
    exit;
}

// Fetch Contact Inquiries
$contacts = $conn->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Responses - Glossera Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <?php include 'header.php'; ?>
  <div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold text-red-600 mb-4">📨 Contact Responses</h1>

    <table class="w-full border-collapse border border-gray-300">
      <thead class="bg-gray-200">
        <tr>
          <th class="border p-2">#</th>
          <th class="border p-2">Name</th>
          <th class="border p-2">Email</th>
          <th class="border p-2">Phone</th>
          <th class="border p-2">Car</th>
          <th class="border p-2">Service</th>
          <th class="border p-2">Message</th>
          <th class="border p-2">Date</th>
          <th class="border p-2">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $contacts->fetch_assoc()): ?>
          <tr>
            <td class="border p-2"><?= $row['id'] ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['name']) ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['email']) ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['phone']) ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['car']) ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['service']) ?></td>
            <td class="border p-2"><?= htmlspecialchars($row['message']) ?></td>
            <td class="border p-2"><?= $row['created_at'] ?></td>
            <td class="border p-2">
              <form method="POST" class="flex space-x-2">
                <input type="hidden" name="contact_id" value="<?= $row['id'] ?>">
                <select name="status" class="border rounded px-2 py-1">
                  <option value="unread" <?= $row['status']=="unread"?"selected":"" ?>>Unread</option>
                  <option value="read" <?= $row['status']=="read"?"selected":"" ?>>Read</option>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded">Update</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

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

// ==================== DATES ====================
$today      = date("Y-m-d");
$weekStart  = date("Y-m-d", strtotime("monday this week"));
$monthStart = date("Y-m-01");

$lastWeekStart  = date("Y-m-d", strtotime("monday last week"));
$lastWeekEnd    = date("Y-m-d", strtotime("sunday last week"));
$lastMonthStart = date("Y-m-01", strtotime("first day of last month"));
$lastMonthEnd   = date("Y-m-t", strtotime("last day of last month"));

// ==================== BOOKINGS STATS ====================
$todayBookings     = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at)='$today'")->fetch_assoc()['total'];
$thisWeekBookings  = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at) >= '$weekStart'")->fetch_assoc()['total'];
$thisMonthBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at) >= '$monthStart'")->fetch_assoc()['total'];
$lastWeekBookings  = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at) BETWEEN '$lastWeekStart' AND '$lastWeekEnd'")->fetch_assoc()['total'];
$lastMonthBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at) BETWEEN '$lastMonthStart' AND '$lastMonthEnd'")->fetch_assoc()['total'];

$weekGrowthBookings  = $lastWeekBookings > 0 ? round((($thisWeekBookings - $lastWeekBookings) / $lastWeekBookings) * 100) : 100;
$monthGrowthBookings = $lastMonthBookings > 0 ? round((($thisMonthBookings - $lastMonthBookings) / $lastMonthBookings) * 100) : 100;

// ==================== CONTACT STATS ====================
$todayContacts     = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at)='$today'")->fetch_assoc()['total'];
$thisWeekContacts  = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at) >= '$weekStart'")->fetch_assoc()['total'];
$thisMonthContacts = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at) >= '$monthStart'")->fetch_assoc()['total'];
$lastWeekContacts  = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at) BETWEEN '$lastWeekStart' AND '$lastWeekEnd'")->fetch_assoc()['total'];
$lastMonthContacts = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at) BETWEEN '$lastMonthStart' AND '$lastMonthEnd'")->fetch_assoc()['total'];

$weekGrowthContacts  = $lastWeekContacts > 0 ? round((($thisWeekContacts - $lastWeekContacts) / $lastWeekContacts) * 100) : 100;
$monthGrowthContacts = $lastMonthContacts > 0 ? round((($thisMonthContacts - $lastMonthContacts) / $lastMonthContacts) * 100) : 100;

// ==================== WEEKLY & MONTHLY DATA ====================
$weeklyDataBookings = [];
$weeklyDataContacts = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-$i day"));
    $bookingsCount = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE(created_at)='$day'")->fetch_assoc()['total'];
    $contactsCount = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE(created_at)='$day'")->fetch_assoc()['total'];
    $weeklyDataBookings[] = ["date" => $day, "count" => $bookingsCount];
    $weeklyDataContacts[] = ["date" => $day, "count" => $contactsCount];
}

$monthlyDataBookings = [];
$monthlyDataContacts = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("-$i month"));
    $bookingsCount = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['total'];
    $contactsCount = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_assoc()['total'];
    $monthlyDataBookings[] = ["month" => $month, "count" => $bookingsCount];
    $monthlyDataContacts[] = ["month" => $month, "count" => $contactsCount];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Glossera</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
  <?php include 'header.php'; ?>
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-red-600 mb-6">📊 Admin Dashboard</h1>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <!-- Bookings Today -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">Today’s Service Requests</h2>
        <p class="text-3xl font-bold text-blue-600"><?= $todayBookings ?></p>
      </div>
      <!-- Bookings This Week -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">This Week</h2>
        <p class="text-3xl font-bold text-green-600"><?= $thisWeekBookings ?></p>
        <p class="text-gray-600">Growth: <span class="<?= $weekGrowthBookings>=0?'text-green-600':'text-red-600' ?>"><?= $weekGrowthBookings ?>%</span></p>
      </div>
      <!-- Bookings This Month -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">This Month</h2>
        <p class="text-3xl font-bold text-purple-600"><?= $thisMonthBookings ?></p>
        <p class="text-gray-600">Growth: <span class="<?= $monthGrowthBookings>=0?'text-green-600':'text-red-600' ?>"><?= $monthGrowthBookings ?>%</span></p>
      </div>

      <!-- Contacts Today -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">Today’s Contact Inquiries</h2>
        <p class="text-3xl font-bold text-blue-600"><?= $todayContacts ?></p>
      </div>
      <!-- Contacts This Week -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">This Week</h2>
        <p class="text-3xl font-bold text-green-600"><?= $thisWeekContacts ?></p>
        <p class="text-gray-600">Growth: <span class="<?= $weekGrowthContacts>=0?'text-green-600':'text-red-600' ?>"><?= $weekGrowthContacts ?>%</span></p>
      </div>
      <!-- Contacts This Month -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">This Month</h2>
        <p class="text-3xl font-bold text-purple-600"><?= $thisMonthContacts ?></p>
        <p class="text-gray-600">Growth: <span class="<?= $monthGrowthContacts>=0?'text-green-600':'text-red-600' ?>"><?= $monthGrowthContacts ?>%</span></p>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Weekly Chart -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-4">📈 Requests - Last 7 Days</h2>
        <canvas id="weeklyChart"></canvas>
      </div>

      <!-- Monthly Chart -->
      <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-4">📉 Requests - Last 6 Months</h2>
        <canvas id="monthlyChart"></canvas>
      </div>
    </div>
  </div>

<script>
  const weeklyDataBookings = <?= json_encode($weeklyDataBookings) ?>;
  const weeklyDataContacts = <?= json_encode($weeklyDataContacts) ?>;
  const monthlyDataBookings = <?= json_encode($monthlyDataBookings) ?>;
  const monthlyDataContacts = <?= json_encode($monthlyDataContacts) ?>;

  // Weekly Chart (Line)
  new Chart(document.getElementById('weeklyChart'), {
    type: 'line',
    data: {
      labels: weeklyDataBookings.map(d => d.date),
      datasets: [
        {
          label: 'Service Requests',
          data: weeklyDataBookings.map(d => d.count),
          borderColor: 'rgb(239,68,68)',
          backgroundColor: 'rgba(239,68,68,0.2)',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Contact Inquiries',
          data: weeklyDataContacts.map(d => d.count),
          borderColor: 'rgb(59,130,246)',
          backgroundColor: 'rgba(59,130,246,0.2)',
          tension: 0.3,
          fill: true
        }
      ]
    }
  });

  // Monthly Chart (Bar)
  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
      labels: monthlyDataBookings.map(d => d.month),
      datasets: [
        {
          label: 'Service Requests',
          data: monthlyDataBookings.map(d => d.count),
          backgroundColor: 'rgba(239,68,68,0.7)'
        },
        {
          label: 'Contact Inquiries',
          data: monthlyDataContacts.map(d => d.count),
          backgroundColor: 'rgba(59,130,246,0.7)'
        }
      ]
    }
  });
</script>
</body>
</html>

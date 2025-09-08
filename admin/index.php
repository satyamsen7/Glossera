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

// ==================== READ / UNREAD CONTACTS ====================
$unreadContacts = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE status='unread'")->fetch_assoc()['total'];
$readContacts   = $conn->query("SELECT COUNT(*) AS total FROM contact_inquiries WHERE status='read'")->fetch_assoc()['total'];

// ==================== READ / UNREAD BOOKINGS ====================
$unreadBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='unread'")->fetch_assoc()['total'];
$readBookings   = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='read'")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Glossera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        @media (max-width: 640px) {
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'header.php'; ?>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="gradient-bg rounded-2xl p-6 sm:p-8 text-white shadow-2xl">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold mb-2">
                            <i class="fas fa-chart-line mr-3"></i>Admin Dashboard
                        </h1>
                        <p class="text-blue-100 text-lg">Welcome back, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>!</p>
                        <p class="text-blue-200 text-sm mt-1"><?= date('l, F j, Y') ?></p>
                    </div>
                    <!-- <div class="mt-4 sm:mt-0 text-right">
                        <div class="text-2xl font-semibold"><?= date('H:i') ?></div>
                        <div class="text-sm text-blue-200">Current Time</div>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Quick Stats - Priority Items -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <!-- Unread Contacts -->
            <div class="stat-card p-4 sm:p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center mb-2">
                            <i class="fas fa-envelope-open-text text-red-500 text-lg mr-2"></i>
                            <h3 class="text-sm font-semibold text-gray-700">Unread Contact Inquiries</h3>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold text-red-600"><?= $unreadContacts ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
            </div>

            <!-- Read Contacts -->
            <div class="stat-card p-4 sm:p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center mb-2">
                            <i class="fas fa-check-circle text-green-500 text-lg mr-2"></i>
                            <h3 class="text-sm font-semibold text-gray-700">Read Contact Inquiries</h3>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold text-green-600"><?= $readContacts ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Unread Bookings -->
            <div class="stat-card p-4 sm:p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center mb-2">
                            <i class="fas fa-clipboard-list text-orange-500 text-lg mr-2"></i>
                            <h3 class="text-sm font-semibold text-gray-700">Unread Requests</h3>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold text-orange-600"><?= $unreadBookings ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600"></i>
                    </div>
                </div>
            </div>

            <!-- Read Bookings -->
            <div class="stat-card p-4 sm:p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center mb-2">
                            <i class="fas fa-clipboard-check text-blue-500 text-lg mr-2"></i>
                            <h3 class="text-sm font-semibold text-gray-700">Read Requests</h3>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold text-blue-600"><?= $readBookings ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-eye text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Requests Overview -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-concierge-bell mr-3 text-indigo-600"></i>Service Requests Analytics
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Today's Bookings -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Today</h3>
                        <i class="fas fa-calendar-day text-2xl text-blue-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-blue-600 mb-2"><?= $todayBookings ?></p>
                    <p class="text-sm text-gray-600">Service requests today</p>
                </div>

                <!-- This Week -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">This Week</h3>
                        <i class="fas fa-calendar-week text-2xl text-green-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-green-600 mb-2"><?= $thisWeekBookings ?></p>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Growth:</span>
                        <span class="<?= $weekGrowthBookings >= 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold flex items-center">
                            <i class="fas fa-arrow-<?= $weekGrowthBookings >= 0 ? 'up' : 'down' ?> mr-1"></i>
                            <?= abs($weekGrowthBookings) ?>%
                        </span>
                    </div>
                </div>

                <!-- This Month -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">This Month</h3>
                        <i class="fas fa-calendar-alt text-2xl text-purple-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-purple-600 mb-2"><?= $thisMonthBookings ?></p>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Growth:</span>
                        <span class="<?= $monthGrowthBookings >= 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold flex items-center">
                            <i class="fas fa-arrow-<?= $monthGrowthBookings >= 0 ? 'up' : 'down' ?> mr-1"></i>
                            <?= abs($monthGrowthBookings) ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Inquiries Overview -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-comments mr-3 text-indigo-600"></i>Contact Inquiries Analytics
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Today's Contacts -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Today</h3>
                        <i class="fas fa-calendar-day text-2xl text-blue-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-blue-600 mb-2"><?= $todayContacts ?></p>
                    <p class="text-sm text-gray-600">Contact inquiries today</p>
                </div>

                <!-- This Week -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">This Week</h3>
                        <i class="fas fa-calendar-week text-2xl text-green-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-green-600 mb-2"><?= $thisWeekContacts ?></p>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Growth:</span>
                        <span class="<?= $weekGrowthContacts >= 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold flex items-center">
                            <i class="fas fa-arrow-<?= $weekGrowthContacts >= 0 ? 'up' : 'down' ?> mr-1"></i>
                            <?= abs($weekGrowthContacts) ?>%
                        </span>
                    </div>
                </div>

                <!-- This Month -->
                <div class="stat-card p-6 rounded-xl shadow-lg card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">This Month</h3>
                        <i class="fas fa-calendar-alt text-2xl text-purple-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-purple-600 mb-2"><?= $thisMonthContacts ?></p>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Growth:</span>
                        <span class="<?= $monthGrowthContacts >= 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold flex items-center">
                            <i class="fas fa-arrow-<?= $monthGrowthContacts >= 0 ? 'up' : 'down' ?> mr-1"></i>
                            <?= abs($monthGrowthContacts) ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Weekly Trends Chart -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center mb-6">
                    <i class="fas fa-chart-line text-xl text-indigo-600 mr-3"></i>
                    <h2 class="text-xl font-bold text-gray-800">Last 7 Days Trends</h2>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>

            <!-- Inquiries Status Pie Chart -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center mb-6">
                    <i class="fas fa-chart-pie text-xl text-indigo-600 mr-3"></i>
                    <h2 class="text-xl font-bold text-gray-800">Inquiries Status</h2>
                </div>
                <div class="chart-container">
                    <canvas id="inquiriesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-chart-bar text-xl text-indigo-600 mr-3"></i>
                <h2 class="text-xl font-bold text-gray-800">Monthly Overview - Last 6 Months</h2>
            </div>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const weeklyDataBookings = <?= json_encode($weeklyDataBookings) ?>;
        const weeklyDataContacts = <?= json_encode($weeklyDataContacts) ?>;
        const monthlyDataBookings = <?= json_encode($monthlyDataBookings) ?>;
        const monthlyDataContacts = <?= json_encode($monthlyDataContacts) ?>;

        // Common chart options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        };

        // Weekly Chart (Line)
        new Chart(document.getElementById('weeklyChart'), {
            type: 'line',
            data: {
                labels: weeklyDataBookings.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Service Requests',
                        data: weeklyDataBookings.map(d => d.count),
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#8B5CF6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    },
                    {
                        label: 'Contact Inquiries',
                        data: weeklyDataContacts.map(d => d.count),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });

        // Monthly Chart (Bar)
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyDataBookings.map(d => {
                    const [year, month] = d.month.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Service Requests',
                        data: monthlyDataBookings.map(d => d.count),
                        backgroundColor: 'rgba(139, 92, 246, 0.8)',
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: 'Contact Inquiries',
                        data: monthlyDataContacts.map(d => d.count),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderRadius: 8,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Inquiries Pie Chart
        new Chart(document.getElementById('inquiriesChart'), {
            type: 'doughnut',
            data: {
                labels: ['Unread Inquiries', 'Read Inquiries'],
                datasets: [{
                    data: [<?= $unreadContacts ?>, <?= $readContacts ?>],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        '#EF4444',
                        '#22C55E'
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                ...commonOptions,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Add fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
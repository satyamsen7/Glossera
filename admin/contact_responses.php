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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR car LIKE ? OR service LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'sssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$query = "SELECT * FROM contact_inquiries $where_clause ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $contacts = $stmt->get_result();
} else {
    $contacts = $conn->query($query);
}

// Get counts for stats
$total_contacts = $conn->query("SELECT COUNT(*) as count FROM contact_inquiries")->fetch_assoc()['count'];
$unread_contacts = $conn->query("SELECT COUNT(*) as count FROM contact_inquiries WHERE status='unread'")->fetch_assoc()['count'];
$read_contacts = $conn->query("SELECT COUNT(*) as count FROM contact_inquiries WHERE status='read'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Responses - Glossera Admin</title>
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
        .status-unread {
            background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
            border-left: 4px solid #f59e0b;
        }
        .status-read {
            background: linear-gradient(135deg, #dcfce7 0%, #86efac 100%);
            border-left: 4px solid #22c55e;
        }
        .message-preview {
            max-height: 60px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .mobile-card {
                display: block !important;
            }
            .desktop-table {
                display: none !important;
            }
        }
        @media (min-width: 769px) {
            .mobile-card {
                display: none !important;
            }
            .desktop-table {
                display: table !important;
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
                        <h1 class="text-3xl sm:text-4xl font-bold mb-2 flex items-center">
                            <i class="fas fa-envelope-open-text mr-3"></i>Contact Inquiries
                        </h1>
                        <p class="text-blue-100 text-lg">Manage customer inquiries and communications</p>
                    </div>
                    <div class="mt-4 sm:mt-0 text-right">
                        <div class="text-2xl font-semibold"><?= $total_contacts ?></div>
                        <div class="text-sm text-blue-200">Total Inquiries</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Inquiries</h3>
                        <p class="text-3xl font-bold text-blue-600"><?= $total_contacts ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-inbox text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Unread</h3>
                        <p class="text-3xl font-bold text-orange-600"><?= $unread_contacts ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Read</h3>
                        <p class="text-3xl font-bold text-green-600"><?= $read_contacts ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-2"></i>Search
                    </label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by name, email, phone, car, or service..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="sm:w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter mr-2"></i>Status
                    </label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="unread" <?= $status_filter === 'unread' ? 'selected' : '' ?>>Unread</option>
                        <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Read</option>
                    </select>
                </div>
                
                <div class="sm:w-32 flex items-end">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2 rounded-lg hover:from-blue-700 hover:to-blue-800 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Desktop Table View -->
        <div class="desktop-table bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="table-responsive">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle & Service</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($contacts->num_rows > 0): ?>
                            <?php while ($row = $contacts->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200 <?= $row['status'] === 'unread' ? 'bg-orange-50 border-l-4 border-orange-400' : '' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $row['id'] ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($row['email']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-phone mr-1"></i><?= htmlspecialchars($row['phone']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <i class="fas fa-car mr-2 text-blue-500"></i><?= htmlspecialchars($row['car']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-wrench mr-2 text-green-500"></i><?= htmlspecialchars($row['service']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 message-preview">
                                            <?= htmlspecialchars($row['message']) ?>
                                        </div>
                                        <?php if (strlen($row['message']) > 100): ?>
                                            <button onclick="toggleMessage(<?= $row['id'] ?>)" class="text-blue-600 hover:text-blue-800 text-xs mt-1">
                                                Show more
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div><?= date('M j, Y', strtotime($row['created_at'])) ?></div>
                                        <div class="text-xs"><?= date('g:i A', strtotime($row['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="contact_id" value="<?= $row['id'] ?>">
                                            <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="unread" <?= $row['status'] == "unread" ? "selected" : "" ?>>Unread</option>
                                                <option value="read" <?= $row['status'] == "read" ? "selected" : "" ?>>Read</option>
                                            </select>
                                            <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-3 py-1 rounded-lg hover:from-blue-700 hover:to-blue-800 transition duration-200">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                    <div class="text-lg font-medium">No contact inquiries found</div>
                                    <div class="text-sm">Try adjusting your search or filter criteria</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-card space-y-4">
            <?php 
            // Reset the result set for mobile view
            if (!empty($params)) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param($param_types, ...$params);
                $stmt->execute();
                $contacts = $stmt->get_result();
            } else {
                $contacts = $conn->query($query);
            }
            ?>
            
            <?php if ($contacts->num_rows > 0): ?>
                <?php while ($row = $contacts->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover <?= $row['status'] === 'unread' ? 'status-unread' : 'status-read' ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                    #<?= $row['id'] ?>
                                </span>
                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status'] === 'unread' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800' ?>">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 text-right">
                                <div><?= date('M j, Y', strtotime($row['created_at'])) ?></div>
                                <div><?= date('g:i A', strtotime($row['created_at'])) ?></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= htmlspecialchars($row['name']) ?></h3>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-envelope w-4 text-blue-500 mr-3"></i>
                                    <span><?= htmlspecialchars($row['email']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-phone w-4 text-green-500 mr-3"></i>
                                    <span><?= htmlspecialchars($row['phone']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-car w-4 text-purple-500 mr-3"></i>
                                    <span><?= htmlspecialchars($row['car']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-wrench w-4 text-orange-500 mr-3"></i>
                                    <span><?= htmlspecialchars($row['service']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Message:</h4>
                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                <?= nl2br(htmlspecialchars($row['message'])) ?>
                            </div>
                        </div>

                        <form method="POST" class="flex items-center justify-between">
                            <input type="hidden" name="contact_id" value="<?= $row['id'] ?>">
                            <select name="status" class="flex-1 mr-3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="unread" <?= $row['status'] == "unread" ? "selected" : "" ?>>Mark as Unread</option>
                                <option value="read" <?= $row['status'] == "read" ? "selected" : "" ?>>Mark as Read</option>
                            </select>
                            <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2 rounded-lg hover:from-blue-700 hover:to-blue-800 transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i>Update
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No contact inquiries found</h3>
                    <p class="text-gray-600">Try adjusting your search or filter criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleMessage(id) {
            // This function can be expanded to show/hide full messages
            console.log('Toggle message for ID:', id);
        }

        // Add fade-in animation for cards
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

        // Auto-submit form when status changes (for better UX)
        document.addEventListener('change', function(e) {
            if (e.target.name === 'status') {
                // Optional: Add confirmation or auto-submit
                // e.target.closest('form').submit();
            }
        });
    </script>
</body>
</html>
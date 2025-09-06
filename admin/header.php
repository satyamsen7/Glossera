<?php
$admin_id = $_SESSION['admin_id'];
// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>

<header class="bg-neutral text-text-body w-full fixed top-0 left-0 -md z-50 h-16">
<style>
  header {
    background: inherit;
    z-index: 9999 !important;
    position: fixed;
    left: 0;
    top: 0;
    width: 100vw;
    height: 4rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
</style>
  <div class="max-w-7xl mx-auto w-full h-16 flex items-center justify-between px-4 md:px-6">
    
    <!-- Left: Logo -->
    <a href="../index.html" class="flex items-center flex-shrink-0">
      <img src="../assets/logo.png" alt="Logo" 
           class="h-10 w-auto object-contain sm:h-12" />
    </a>

   

    <!-- Right: Profile -->
    <div class="relative">
      <button id="profileToggle"
              class="flex items-center gap-3 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors"
              aria-haspopup="true" aria-expanded="false">
        
        <!-- Profile Avatar with improved styling -->
        <div class="relative">
          <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-md">
            <!-- User Icon (Modern Design) -->
            <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>
          <!-- Online status indicator -->
          <div class="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-green-400 rounded-full border-2 border-white"></div>
        </div>

        <!-- Username (hidden on mobile) -->
        <span class="hidden sm:block text-sm font-medium text-gray-700"><?= htmlspecialchars($admin['name']) ?></span>

        <!-- Dropdown Arrow -->
        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="dropdownArrow">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <!-- Enhanced Dropdown -->
      <div id="profileMenu"
           class="hidden absolute right-0 top-full mt-2 w-[86vw] sm:w-64 max-w-[90vw] bg-white text-black 
                  rounded-xl shadow-xl ring-1 ring-black/5 overflow-hidden z-[99999] max-h-[75vh] overflow-auto border border-gray-100">
        
        <!-- User Info Header -->
        <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
              <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <div>
              <p class="font-semibold text-gray-800"><?= htmlspecialchars($admin['name']) ?></p>
              <p class="text-sm text-gray-600">Administrator</p>
            </div>
          </div>
        </div>

        <!-- Menu Items with Icons -->
        <div class="py-2">
  <!-- Dashboard - Grid/Layout icon -->
  <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
    </svg>
    <span>Dashboard</span>
  </a>
  
  <!-- Service Requests - Clipboard/List icon -->
  <a href="service_requests.php" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
    </svg>
    <span>Service Requests</span>
  </a>
  
  <!-- Contact Responses - Chat/Message icon -->
  <a href="contact_responses.php" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <span>Contact Inquiries</span>
  </a>
  
  <!-- Profile - User icon -->
  <a href="profile.php" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
  </a>
</div>
          
        
        
        <!-- Separator -->
        <div class="border-t border-gray-100"></div>
        
        <!-- Logout -->
        <div class="py-2">
          <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-red-600 hover:bg-red-50 font-semibold transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Log Out</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Spacer -->
<div class="h-16"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('profileToggle');
  const menu   = document.getElementById('profileMenu');
  const arrow  = document.getElementById('dropdownArrow');

  const closeMenu = () => {
    menu.classList.add('hidden');
    toggle.setAttribute('aria-expanded', 'false');
    arrow.style.transform = 'rotate(0deg)';
  };

  const openMenu = () => {
    menu.classList.remove('hidden');
    toggle.setAttribute('aria-expanded', 'true');
    arrow.style.transform = 'rotate(180deg)';
  };

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    if (menu.classList.contains('hidden')) {
      openMenu();
    } else {
      closeMenu();
    }
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!menu.classList.contains('hidden') &&
        !menu.contains(e.target) &&
        !toggle.contains(e.target)) {
      closeMenu();
    }
  });

  // Close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});
</script>
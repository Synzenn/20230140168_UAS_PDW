<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login
if (!isset($_SESSION['user_id'])) {
    // Path yang benar dari mahasiswa/ ke root
    header("Location: ../login.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #2d3748; /* Darker track for dark theme */
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #4a5568; /* Darker thumb for dark theme */
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased">

    <nav class="bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20"> <!-- Increased height -->
                
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-white text-3xl font-bold">LEIRA </span> <!-- Larger text -->
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-6"> <!-- Increased space -->
                            <?php 
                                $activeClass = 'bg-blue-700 text-white shadow-md';
                                $inactiveClass = 'text-gray-300 hover:bg-gray-700 hover:text-white';
                            ?>
                            <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> px-4 py-2.5 rounded-md text-lg font-medium transition-colors duration-200">Dashboard</a>
                            <a href="my_courses.php" class="<?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> px-4 py-2.5 rounded-md text-lg font-medium">Praktikum Saya</a>
                            <a href="courses.php" class="<?php echo ($activePage == 'courses') ? $activeClass : $inactiveClass; ?> px-4 py-2.5 rounded-md text-lg font-medium">Cari Praktikum</a>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6 space-x-6"> <!-- Increased space -->
                        <span class="text-gray-300 text-lg">Selamat Datang, <span class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nama']); ?></span></span>
                        <!-- Path yang benar dari mahasiswa/ ke logout.php di root -->
                        <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-md transition-colors duration-300 text-lg shadow-md">
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex md:hidden">
                    <button type="button" class="bg-gray-800 inline-flex items-center justify-center p-3 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                        <span class="sr-only">Open main menu</span>
                        <!-- Icon when menu is closed. -->
                        <!-- Heroicon name: outline/menu -->
                        <svg class="block h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Icon when menu is open. -->
                        <!-- Heroicon name: outline/x -->
                        <svg class="hidden h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu, show/hide based on menu state. -->
        <div class="md:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> block px-4 py-2.5 rounded-md text-base font-medium transition-colors duration-200">Dashboard</a>
                <a href="my_courses.php" class="<?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> block px-4 py-2.5 rounded-md text-base font-medium">Praktikum Saya</a>
                <a href="courses.php" class="<?php echo ($activePage == 'courses') ? $activeClass : $inactiveClass; ?> block px-4 py-2.5 rounded-md text-base font-medium">Cari Praktikum</a>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white block px-4 py-2.5 rounded-md text-base font-medium transition-colors duration-300 mt-2">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-8 lg:p-10 min-h-[calc(100vh-128px)]">
    <script>
        // JavaScript for mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true' || false;
                this.setAttribute('aria-expanded', !expanded);
                mobileMenu.classList.toggle('hidden');
                // Toggle icons
                this.querySelector('svg:first-child').classList.toggle('hidden'); // Menu icon
                this.querySelector('svg:last-child').classList.toggle('hidden');  // X icon
            });
        });
    </script>

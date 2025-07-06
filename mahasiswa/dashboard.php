<?php
$pageTitle = 'Dashboard';
require_once 'templates/header_mahasiswa.php';
?>

<div class="bg-gray-800 p-8 lg:p-10 rounded-lg shadow-xl mb-8">
    <h1 class="text-4xl lg:text-5xl font-extrabold text-white mb-8">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="text-lg lg:text-xl text-gray-300 mb-10">Ini adalah panel dashboard Anda. Gunakan navigasi di atas untuk menjelajahi fitur-fitur SIMPRAK.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="bg-gray-700 p-8 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 transform hover:scale-105">
            <h2 class="text-2xl font-semibold text-blue-400 mb-4"><i class="fas fa-book-open mr-3 text-2xl"></i>Praktikum Saya</h2>
            <p class="text-gray-300 text-lg mb-6">Lihat daftar praktikum yang sedang Anda ikuti.</p>
            <a href="my_courses.php" class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-md hover:bg-blue-700 transition-colors duration-300 text-lg shadow-md">Lihat Praktikum</a>
        </div>
        <div class="bg-gray-700 p-8 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 transform hover:scale-105">
            <h2 class="text-2xl font-semibold text-green-400 mb-4"><i class="fas fa-search mr-3 text-2xl"></i>Cari Praktikum</h2>
            <p class="text-gray-300 text-lg mb-6">Temukan dan daftar ke mata praktikum baru.</p>
            <a href="courses.php" class="inline-block bg-green-600 text-white font-bold py-3 px-6 rounded-md hover:bg-green-700 transition-colors duration-300 text-lg shadow-md">Cari Sekarang</a>
        </div>
        <div class="bg-gray-700 p-8 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300 transform hover:scale-105">
            <h2 class="text-2xl font-semibold text-purple-400 mb-4"><i class="fas fa-file-upload mr-3 text-2xl"></i>Kumpulkan Laporan</h2>
            <p class="text-gray-300 text-lg mb-6">Akses detail praktikum untuk mengunggah laporan Anda.</p>
            <a href="my_courses.php" class="inline-block bg-purple-600 text-white font-bold py-3 px-6 rounded-md hover:bg-purple-700 transition-colors duration-300 text-lg shadow-md">Unggah Laporan</a>
        </div>
    </div>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'WLC Application'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('/img/logo.png') ?>" type="image/png">
    <!-- Memuat CSS dengan parameter versi untuk cache busting -->
    <link rel="stylesheet" href="<?= base_url('/css/style.css?v=1.3') ?>">
    
    <!-- Slot untuk memuat file CSS spesifik halaman -->
    <?php if (isset($page_styles) && is_array($page_styles)): ?>
        <?php foreach ($page_styles as $style): ?>
            <link rel="stylesheet" href="<?= base_url('/' . $style . '?v=1.0') ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Menambahkan library Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        // Variabel global untuk memudahkan AJAX di JavaScript
        let BASE_URL = "<?= base_url('/') ?>";
        // Fix Mixed Content: Jika browser di HTTPS tapi BASE_URL HTTP, upgrade ke HTTPS
        if (window.location.protocol === 'https:' && BASE_URL.startsWith('http:')) {
            BASE_URL = BASE_URL.replace('http:', 'https:');
        }
    </script>
</head>
<body>
    <div class="main-container">
        <?php require_once 'sidebar.php'; // Memuat sidebar dari file terpisah ?>
        <main class="content">
            <?php require_once $content; // PERBAIKAN: Menggunakan variabel $content yang benar dari index.php ?>
        </main>
    </div>

    <?php
    // Logika Notifikasi Global
    $notifMsg = '';
    $notifType = '';
    if (isset($_SESSION['success_message'])) {
        $notifMsg = $_SESSION['success_message'];
        $notifType = 'success';
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        $notifMsg = $_SESSION['error_message'];
        $notifType = 'error';
        unset($_SESSION['error_message']);
    } elseif (isset($_SESSION['error'])) {
        $notifMsg = $_SESSION['error'];
        $notifType = 'error';
        unset($_SESSION['error']);
    }
    ?>

    <!-- Modal Notifikasi -->
    <div id="notificationModal" class="notif-modal" style="display: <?php echo $notifMsg ? 'flex' : 'none'; ?>;">
        <div class="notif-modal-content <?php echo $notifType === 'success' ? 'notif-success' : 'notif-error'; ?>">
            <div class="notif-icon">
                <i class="fas <?php echo $notifType === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
            </div>
            <h3 class="notif-title"><?php echo $notifType === 'success' ? 'Berhasil!' : 'Gagal!'; ?></h3>
            <p class="notif-message"><?php echo htmlspecialchars($notifMsg); ?></p>
            <button class="btn btn-primary" onclick="document.getElementById('notificationModal').style.display='none'">Tutup</button>
        </div>
    </div>

    <!-- PERBAIKAN: Slot untuk memuat file JavaScript spesifik halaman -->
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?= base_url('/' . $script . '?v=1.0') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
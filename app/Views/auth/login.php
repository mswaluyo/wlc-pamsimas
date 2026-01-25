<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WLC Pamsimas</title>
    <!-- Menggunakan CSS utama agar tema seragam -->
    <link rel="stylesheet" href="<?= base_url('/css/style.css?v=1.1') ?>">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1 style="text-align: center; border-bottom: none;">WLC Login</h1>

        <?php
        $errorMsg = '';
        if (isset($_SESSION['error'])) {
            $errorMsg = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        ?>

        <!-- Modal Notifikasi Login -->
        <div id="notificationModal" class="notif-modal" style="display: <?php echo $errorMsg ? 'flex' : 'none'; ?>;">
            <div class="notif-modal-content">
                <div class="notif-icon"><i class="fas fa-times-circle"></i></div>
                <h3 class="notif-title">Login Gagal</h3>
                <p class="notif-message"><?php echo htmlspecialchars($errorMsg); ?></p>
                <button class="btn btn-primary" onclick="document.getElementById('notificationModal').style.display='none'">Coba Lagi</button>
            </div>
        </div>

        <form action="<?= base_url('/login') ?>" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
    </div>
</body>
</html>
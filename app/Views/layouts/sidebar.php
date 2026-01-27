<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= base_url('/') ?>" class="logo-link" style="display: flex; align-items: center; justify-content: center; text-decoration: none; color: white;">
            <img src="<?= base_url('/img/logo.png') ?>" alt="Logo" style="width: 35px; height: auto; margin-right: 10px;">
            <h3 style="margin: 0;">WLC Pamsimas</h3>
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?= base_url('/') ?>">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-header">PENGATURAN</li>
            <li class="nav-item">
                <a href="<?= base_url('/detect') ?>">
                    <i class="fas fa-search nav-icon"></i>
                    <span>Deteksi Perangkat</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/controllers') ?>">
                    <i class="fas fa-microchip nav-icon"></i>
                    <span>Perangkat</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/settings/tanks') ?>">
                    <i class="fa-solid fa-glass-water nav-icon"></i>
                    <span>Data Tangki</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/settings/pumps') ?>">
                    <i class="fas fa-fan nav-icon"></i>
                    <span>Data Pompa</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/settings/sensors') ?>">
                    <i class="fas fa-satellite-dish nav-icon"></i>
                    <span>Data Sensor</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/settings/display') ?>" class="<?= isActive('/settings/display') ?>">
                    <i class="fas fa-palette nav-icon"></i>
                    <span>Pengaturan Tampilan</span>
                </a>
            </li>

            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Administrator'): ?>
            <li class="nav-item">
                <a href="<?= base_url('/users') ?>">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <span>Manajemen Pengguna</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/settings/maintenance') ?>">
                    <i class="fas fa-tools nav-icon"></i>
                    <span>Maintenance</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-header">ANALISIS & LOG</li>
            <li class="nav-item"> 
                <a href="<?= base_url('/logs/sensors') ?>" class="<?= isActive('/logs/sensors') ?>">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span>Log Sensor</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('/logs/pumps') ?>" class="<?= isActive('/logs/pumps') ?>">
                    <i class="fas fa-history nav-icon"></i>
                    <span>Riwayat Pompa</span>
                </a>
            </li>

            <li class="nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt nav-icon"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    <form id="logout-form" action="<?= base_url('/logout') ?>" method="POST" style="display: none;"></form>
</aside>
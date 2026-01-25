<div class="card">
    <h1><?php echo $title ?? 'Pengaturan Perangkat'; ?></h1>
    <p>Kelola perangkat kontroler WLC (Water Level Controller) yang terhubung ke sistem.</p>

    <?php if (!empty($active_devices)): ?>
        <div class="alert alert-info" style="background-color: #d1ecf1; color: #0c5460; padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #bee5eb;">
            <strong><i class="fas fa-wifi"></i> Perangkat Baru Terdeteksi!</strong>
            <p>Ditemukan <?php echo count($active_devices); ?> perangkat aktif yang belum terdaftar.</p>
            <ul style="margin-bottom: 10px; padding-left: 20px;">
                <?php foreach ($active_devices as $mac): ?>
                    <li>MAC: <strong><?php echo htmlspecialchars($mac); ?></strong> <a href="<?= base_url('/controllers/register?mac=' . urlencode($mac)) ?>" style="margin-left: 10px;">Daftarkan</a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="<?= base_url('/controllers/register') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Daftarkan Perangkat Baru
        </a>
        <a href="<?= base_url('/detect') ?>" class="btn btn-info" style="margin-left: 10px;">
            <i class="fas fa-search"></i> Deteksi Otomatis
        </a>
    </div>

    <!-- Tabel Daftar Perangkat -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Tangki / MAC</th>
                <th>Status</th>
                <th>Mode</th>
                <th>Level Air</th>
                <th>Terakhir Update</th>
                <th style="width: 200px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($controllers)): ?>
                <?php foreach ($controllers as $controller): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($controller['tank_name'] ?? 'Tanpa Nama'); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($controller['mac_address']); ?></small>
                        </td>
                        <td>
                            <?php 
                                $isOnline = (strtotime($controller['last_update']) > (time() - 300));
                                if ($isOnline) {
                                    echo '<span class="status-badge status-online">Online</span>';
                                } else {
                                    echo '<span class="status-badge status-offline">Offline</span>';
                                }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($controller['control_mode']); ?></td>
                        <td><?php echo htmlspecialchars($controller['latest_water_level'] ?? 0); ?>%</td>
                        <td><?php echo htmlspecialchars($controller['last_update']); ?></td>
                        <td class="action-buttons">
                            <!-- Tombol Detail -->
                            <a href="<?= base_url('/controllers/' . $controller['id']) ?>" class="btn btn-sm btn-info" title="Detail"><i class="fas fa-info-circle"></i></a>
                            
                            <!-- Tombol Sync -->
                            <form action="<?= base_url('/controllers/sync/' . $controller['id']) ?>" method="POST" style="display: inline;">
                                <button type="submit" class="btn btn-sm btn-primary" title="Sinkronisasi Data Master" onclick="return confirm('Sinkronisasi akan mengirim pengaturan Tangki, Pompa, dan Sensor terbaru ke perangkat ini. Lanjutkan?')">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </form>

                            <!-- Tombol Apply Settings (Re-send config) -->
                            <form action="<?= base_url('/controllers/apply-settings/' . $controller['id']) ?>" method="POST" style="display: inline;">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Kirim Ulang Konfigurasi" onclick="return confirm('Kirim perintah update konfigurasi ke perangkat?')">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>

                            <!-- Tombol Hapus -->
                            <form action="<?= base_url('/controllers/delete/' . $controller['id']) ?>" method="POST" style="display: inline;">
                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus Perangkat" onclick="return confirm('PERINGATAN: Menghapus perangkat akan menghapus semua log terkait. Tindakan ini tidak dapat dibatalkan. Yakin ingin menghapus?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada perangkat yang didaftarkan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
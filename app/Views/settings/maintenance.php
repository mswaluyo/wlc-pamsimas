<div class="card">
    <h1><?php echo $title ?? 'Maintenance & System'; ?></h1>
    
    <!-- Tab Navigation -->
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'Maintenance')">Maintenance Mode</button>
        <button class="tab-link" onclick="openTab(event, 'Database')">Database</button>
        <button class="tab-link" onclick="openTab(event, 'Logs')">Audit Trail</button>
    </div>

    <!-- Tab 1: Maintenance Mode -->
    <div id="Maintenance" class="tab-content" style="display: block;">
        <h2>Maintenance Mode</h2>
        <p>Fitur ini digunakan untuk membatasi akses ke aplikasi saat sedang dilakukan perbaikan atau pembaruan sistem.</p>

        <div class="alert <?php echo $isMaintenance ? 'alert-danger' : 'alert-info'; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid transparent; <?php echo $isMaintenance ? 'background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;' : 'background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb;'; ?>">
            <strong>Status Saat Ini:</strong> <?php echo $isMaintenance ? 'AKTIF (Situs Tidak Dapat Diakses Publik)' : 'NON-AKTIF (Situs Normal)'; ?>
        </div>

        <form action="<?= base_url('/settings/maintenance/toggle') ?>" method="POST">
            <p>
                <?php if ($isMaintenance): ?>
                    Klik tombol di bawah untuk <strong>mematikan</strong> maintenance mode dan membuat situs kembali online.
                <?php else: ?>
                    Klik tombol di bawah untuk <strong>mengaktifkan</strong> maintenance mode. Pengguna non-admin akan melihat pesan "Service Unavailable".
                <?php endif; ?>
            </p>
            
            <button type="submit" class="btn <?php echo $isMaintenance ? 'btn-success' : 'btn-danger'; ?>" onclick="return confirm('Apakah Anda yakin ingin mengubah status maintenance mode?');">
                <?php echo $isMaintenance ? 'Matikan Maintenance Mode' : 'Aktifkan Maintenance Mode'; ?>
            </button>
        </form>
    </div>

    <!-- Tab 2: Database -->
    <div id="Database" class="tab-content">
        <h2>Backup & Restore Database</h2>
        <p>Kelola cadangan data sistem. Anda dapat memulihkan database dari file .sql yang sebelumnya diekspor.</p>

        <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <h3>Backup Database</h3>
            <p>Unduh salinan lengkap database saat ini dalam format .sql.</p>
            <a href="<?= base_url('/settings/database/backup') ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Download Backup (.sql)
            </a>
        </div>

        <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; padding: 15px; margin-bottom: 20px; border: 1px solid #ffeeba; border-radius: 4px;">
            <strong><i class="fas fa-exclamation-triangle"></i> PERINGATAN PENTING:</strong>
            <p style="margin-bottom: 0;">Tindakan Restore akan <strong>MENGHAPUS/MENIMPA</strong> data saat ini dengan data dari file backup. Pastikan Anda telah melakukan backup data terkini sebelum melakukan restore.</p>
        </div>

        <div style="margin-top: 30px;">
            <h3>Restore Database</h3>
            <form action="<?= base_url('/settings/database/restore') ?>" method="POST" enctype="multipart/form-data" style="max-width: 500px;">
                <div class="form-group">
                    <label for="backup_file">Pilih File Backup (.sql)</label>
                    <input type="file" id="backup_file" name="backup_file" accept=".sql" required class="custom-file-upload" style="width: 100%;">
                    <small>Maksimal ukuran file upload: <?php echo ini_get('upload_max_filesize'); ?>.</small>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('APAKAH ANDA YAKIN? Data saat ini akan ditimpa!');">
                        <i class="fas fa-upload"></i> Upload & Restore
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab 3: Audit Trail (Log) -->
    <div id="Logs" class="tab-content">
        <h2>Audit Trail (Log Admin)</h2>
        <p>Catatan aktivitas administratif yang dilakukan oleh pengguna (Backup, Restore, dll).</p>
        
        <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
            <form method="GET" action="<?= base_url('/settings/maintenance') ?>" style="display: inline-block;">
                <label for="limit" style="margin-right: 5px;">Tampilkan:</label>
                <select name="limit" id="limit" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                    <option value="all" <?= ($limit === 'all') ? 'selected' : '' ?>>Semua</option>
                </select>
            </form>
        </div>

        <div style="margin-top: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                        $actionClass = 'status-badge status-offline'; // Default gray
                                        if (stripos($log['action'], 'Backup') !== false) $actionClass = 'status-badge status-online'; // Green
                                        if (stripos($log['action'], 'Restore') !== false) $actionClass = 'status-badge status-offline'; // Gray/Redish
                                        if (stripos($log['action'], 'Failed') !== false) $actionClass = 'status-badge status-offline'; // Red logic handled by CSS usually
                                    ?>
                                    <span class="<?php echo $actionClass; ?>" style="background-color: #3498db;"><?php echo htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Belum ada aktivitas admin yang tercatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($total_pages) && $total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="<?= base_url('/settings/maintenance') ?>?page=<?= $current_page - 1 ?>&limit=<?= $limit ?>" class="btn btn-sm btn-secondary">&laquo; Sebelumnya</a>
            <?php endif; ?>
            
            <span>Halaman <?= $current_page ?> dari <?= $total_pages ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="<?= base_url('/settings/maintenance') ?>?page=<?= $current_page + 1 ?>&limit=<?= $limit ?>" class="btn btn-sm btn-secondary">Selanjutnya &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Otomatis buka tab Logs jika ada parameter page di URL
if (new URLSearchParams(window.location.search).has('page') || new URLSearchParams(window.location.search).has('limit')) {
    document.querySelector('.tab-link[onclick*="Logs"]').click();
}
</script>
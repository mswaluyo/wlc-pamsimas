<div class="card">
    <h1><?php echo $title ?? 'Riwayat Pompa'; ?></h1>
    <p>Menampilkan riwayat aktivitas pompa menyala (ON) dan mati (OFF) beserta durasinya.</p>

    <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
        <form method="GET" action="<?= base_url('/logs/pumps') ?>" style="display: inline-block;">
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

    <table>
        <thead>
            <tr>
                <th>Waktu Kejadian</th>
                <th>Nama Tangki</th>
                <th>Status</th>
                <th>Durasi Menyala</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d M Y, H:i:s', strtotime($log['record_time'])); ?></td>
                        <td><?php echo htmlspecialchars($log['tank_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($log['status'] == 1): ?>
                                <span style="color: var(--success-color); font-weight: bold;">ON</span>
                            <?php else: ?>
                                <span style="color: #e74c3c; font-weight: bold;">OFF</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['status'] == 0 && !is_null($log['duration_seconds'])): ?>
                                <?php echo floor($log['duration_seconds'] / 60); ?> menit <?php echo $log['duration_seconds'] % 60; ?> detik
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Tidak ada riwayat aktivitas pompa.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
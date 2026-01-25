<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Menampilkan riwayat data level air yang dikirim oleh sensor perangkat.</p>

    <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
        <form method="GET" action="<?= base_url('/logs/sensors') ?>" style="display: inline-block;">
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

    <div>
        <table>
            <thead>
                <tr>
                    <th>Waktu Pencatatan</th>
                    <th>Nama Tangki</th>
                    <th>Level Air</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d M Y, H:i:s', strtotime($log['record_time'])); ?></td>
                            <td><?php echo htmlspecialchars($log['tank_name'] ?? 'N/A'); ?></td>
                            <td><?php echo round($log['water_percentage']); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Tidak ada riwayat data sensor.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
    <div class="pagination">
        <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= $pagination['base_url'] ?>?page=<?= $pagination['current_page'] - 1 ?>&limit=<?= $pagination['limit'] ?>" class="btn btn-sm btn-secondary">&laquo; Sebelumnya</a>
        <?php endif; ?>
        
        <span>Halaman <?= $pagination['current_page'] ?> dari <?= $pagination['total_pages'] ?></span>

        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
            <a href="<?= $pagination['base_url'] ?>?page=<?= $pagination['current_page'] + 1 ?>&limit=<?= $pagination['limit'] ?>" class="btn btn-sm btn-secondary">Selanjutnya &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
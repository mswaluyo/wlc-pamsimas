<div class="card">
    <h1><?php echo $title ?? 'Audit Trail'; ?></h1>
    <p>Catatan aktivitas administratif yang dilakukan oleh pengguna (Backup, Restore, dll).</p>

    <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
        <form method="GET" action="<?= base_url('/logs/admin') ?>" style="display: inline-block;">
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
<div class="card">
    <div class="page-header">
        <h1><?php echo $title ?? 'Log Kejadian'; ?></h1>
        <div>
            <!-- Filter Limit -->
            <select onchange="window.location.href=this.value" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="<?= $pagination['base_url'] ?>?limit=25" <?= $limit == 25 ? 'selected' : '' ?>>25 per halaman</option>
                <option value="<?= $pagination['base_url'] ?>?limit=50" <?= $limit == 50 ? 'selected' : '' ?>>50 per halaman</option>
                <option value="<?= $pagination['base_url'] ?>?limit=100" <?= $limit == 100 ? 'selected' : '' ?>>100 per halaman</option>
                <option value="<?= $pagination['base_url'] ?>?limit=all" <?= $limit == 'all' ? 'selected' : '' ?>>Semua</option>
            </select>
        </div>
    </div>

    <p>Menampilkan riwayat kejadian perangkat seperti Power On (Mati Listrik), Perubahan Mode, dan Status Koneksi.</p>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-number">No</th>
                    <th>Waktu Kejadian</th>
                    <th>Perangkat</th>
                    <th>Tipe Event</th>
                    <th>Pesan / Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = ($pagination['current_page'] - 1) * ($limit === 'all' ? 0 : $limit) + 1;
                ?>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            // Tentukan warna baris berdasarkan tipe event
                            $rowStyle = '';
                            $badgeClass = 'btn-secondary'; // Default abu-abu
                            $icon = 'fa-info-circle';

                            $type = strtolower($log['event_type']);
                            
                            if ($type === 'power on' || strpos($type, 'boot') !== false) {
                                $rowStyle = 'background-color: #e8f4fc;'; // Biru muda
                                $badgeClass = 'btn-primary';
                                $icon = 'fa-bolt';
                            } elseif (strpos($type, 'offline') !== false || strpos($type, 'lost') !== false || strpos($type, 'error') !== false) {
                                $rowStyle = 'background-color: #fce8e8;'; // Merah muda
                                $badgeClass = 'btn-danger';
                                $icon = 'fa-exclamation-triangle';
                            } elseif (strpos($type, 'reconnected') !== false || strpos($type, 'online') !== false) {
                                $rowStyle = 'background-color: #e8fce8;'; // Hijau muda
                                $badgeClass = 'btn-success';
                                $icon = 'fa-wifi';
                            }
                        ?>
                        <tr style="<?= $rowStyle ?>">
                            <td class="col-number"><?= $no++ ?></td>
                            <td><?= date('d M Y, H:i:s', strtotime($log['event_time'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($log['tank_name'] ?? 'Tanpa Nama') ?></strong><br>
                                <small style="color: #666;"><?= htmlspecialchars($log['mac_address']) ?></small>
                            </td>
                            <td>
                                <span class="btn btn-sm <?= $badgeClass ?>" style="padding: 2px 8px; font-size: 0.8rem; cursor: default;">
                                    <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($log['event_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Belum ada data log kejadian.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="<?= $pagination['base_url'] ?>?page=<?= $i ?>&limit=<?= $limit ?>" class="btn btn-sm <?= $i == $pagination['current_page'] ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
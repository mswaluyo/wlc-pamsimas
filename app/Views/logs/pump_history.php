<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Menampilkan riwayat aktivitas pompa menyala (ON) dan mati (OFF) beserta durasinya.</p>

    <div style="margin-bottom: 20px;">
        <form method="GET" action="<?= base_url('/logs/pumps') ?>" style="display: flex; align-items: center; gap: 10px;">
            <label for="device_id">Filter Perangkat:</label>
            <select name="device_id" id="device_id" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="all">Semua Perangkat</option>
                <?php foreach ($controllers as $controller): ?>
                    <option value="<?= $controller['id'] ?>" <?= ($selected_device == $controller['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($controller['tank_name'] ?? $controller['mac_address']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($limit !== 25): ?>
                <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
            <?php endif; ?>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu Kejadian</th>
                    <th>Nama Tangki</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d M Y, H:i:s', strtotime($log['timestamp'])); ?></td>
                            <td><?php echo htmlspecialchars($log['tank_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                    $mode = $log['control_mode'] ?? '-';
                                    // Warna: Hijau untuk AUTO, Oranye untuk MANUAL, Abu-abu untuk lainnya
                                    $badgeColor = ($mode === 'AUTO') ? '#27ae60' : (($mode === 'MANUAL') ? '#f39c12' : '#95a5a6');
                                ?>
                                <span style="background-color: <?= $badgeColor ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold;"><?= htmlspecialchars($mode) ?></span>
                            </td>
                            <td>
                                <?php if ($log['pump_status'] === 'ON'): ?>
                                    <span class="status-badge status-online">ON</span>
                                <?php else: ?>
                                    <!-- Database menyimpan string kosong untuk OFF pada beberapa kasus -->
                                    <span class="status-badge status-offline">OFF</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($log['duration_seconds'])) {
                                        $days = floor($log['duration_seconds'] / 86400);
                                        $hours = floor(($log['duration_seconds'] % 86400) / 3600);
                                        $minutes = floor(($log['duration_seconds'] % 3600) / 60);
                                        $seconds = $log['duration_seconds'] % 60;
                                        echo sprintf('%02d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
                                        
                                        // Tambahkan label penjelas agar tidak bingung
                                        if ($log['pump_status'] === 'ON') {
                                            echo '<div style="font-size: 0.8em; color: #7f8c8d;">(Durasi Istirahat)</div>';
                                        } else {
                                            echo '<div style="font-size: 0.8em; color: #27ae60; font-weight: bold;">(Durasi Menyala)</div>';
                                        }
                                    } else {
                                        echo "-";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Belum ada data log pompa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php 
                    $url = $pagination['base_url'] . '?page=' . $i . '&limit=' . $pagination['limit'];
                    if (!empty($pagination['device_id'])) {
                        $url .= '&device_id=' . $pagination['device_id'];
                    }
                ?>
                <a href="<?php echo $url; ?>" 
                   class="btn btn-sm <?php echo ($i == $pagination['current_page']) ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
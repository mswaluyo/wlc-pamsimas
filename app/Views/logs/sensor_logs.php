<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Menampilkan riwayat data level air yang dikirim oleh sensor perangkat.</p>

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
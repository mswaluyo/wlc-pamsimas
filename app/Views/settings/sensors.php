<div class="card">
    <h1><?php echo $title ?? 'Pengaturan Sensor'; ?></h1>
    <p>Halaman ini digunakan untuk mengelola daftar sensor dan pengaturannya.</p>

    <div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-number">No</th>
                    <th>Nama Sensor</th>
                    <th>Tipe Sensor</th>
                    <th>Jarak Tangki Penuh (cm)</th>
                    <th>Pemicu Pompa</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php if (!empty($sensors)): ?>
                    <?php foreach ($sensors as $sensor): ?>
                        <tr>
                            <td class="col-number"><?= $no++ ?></td>
                            <td><?php echo htmlspecialchars($sensor['sensor_name']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['sensor_type']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['full_tank_distance']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['trigger_percentage']); ?>%</td>
                            <td><?php echo htmlspecialchars($sensor['created_at']); ?></td>
                            <td class="action-buttons">
                                <a href="<?= base_url('/settings/sensors/edit/' . $sensor['id']) ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                            <td colspan="7" style="text-align: center;">Belum ada sensor yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer-actions">
        <a href="<?= base_url('/settings/sensors/create') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Tambah Sensor Baru
        </a>
    </div>
</div>
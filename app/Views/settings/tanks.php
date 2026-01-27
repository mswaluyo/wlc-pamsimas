<div class="card">
    <h1><?php echo $title ?? 'Pengaturan Tangki'; ?></h1>
    <p>Halaman ini digunakan untuk mengelola konfigurasi aset fisik tangki air.</p>

    <!-- Bagian Pengaturan Tangki -->
    <div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-number">No</th>
                    <th>Nama Tangki</th>
                    <th>Bentuk</th>
                    <th>Tinggi (cm)</th>
                    <th>Dimensi (cm)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php if (!empty($tanks)): ?>
                    <?php foreach ($tanks as $tank): ?>
                        <tr>
                            <td class="col-number"><?= $no++ ?></td>
                            <td><?php echo htmlspecialchars($tank['tank_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($tank['tank_shape'])); ?></td>
                            <td><?php echo htmlspecialchars($tank['height']); ?></td>
                            <td>
                                <?php
                                if ($tank['tank_shape'] === 'kotak') {
                                    echo 'P: ' . htmlspecialchars($tank['length']) . ', L: ' . htmlspecialchars($tank['width']);
                                } else if ($tank['tank_shape'] === 'bulat') {
                                    echo 'D: ' . htmlspecialchars($tank['diameter']);
                                }
                                ?>
                            </td>
                            <td class="action-buttons">
                                <a href="<?= base_url('/settings/tanks/edit/' . $tank['id']) ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada tangki yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer-actions">
        <a href="<?= base_url('/settings/tanks/create') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Tambah Tangki Baru
        </a>
    </div>
</div>
<div class="card">
    <h1><?php echo $title ?? 'Pengaturan Pompa'; ?></h1>
    <p>Halaman ini digunakan untuk mengelola konfigurasi aset fisik pompa.</p>

    <div style="margin-bottom: 20px;">
        <a href="<?= base_url('/settings/pumps/create') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Tambah Pompa Baru
        </a>
    </div>

    <!-- Bagian Pengaturan Pompa -->
    <div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Pompa</th>
                    <th>Debit (L/detik)</th>
                    <th>Daya (Watt)</th>
                    <th>Waktu Tunda (detik)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pumps)): ?>
                    <?php foreach ($pumps as $pump): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pump['id']); ?></td>
                            <td><?php echo htmlspecialchars($pump['pump_name']); ?></td>
                            <td><?php echo htmlspecialchars($pump['flow_rate_lps']); ?></td>
                            <td><?php echo htmlspecialchars($pump['power_watt']); ?></td>
                            <td><?php echo htmlspecialchars($pump['delay_seconds']); ?></td>
                            <td><a href="<?= base_url('/settings/pumps/edit/' . $pump['id']) ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada pompa yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
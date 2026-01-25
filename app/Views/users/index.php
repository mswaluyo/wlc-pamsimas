<div class="card">
    <h1><?php echo $title ?? 'Manajemen Pengguna'; ?></h1>
    <p>Kelola pengguna yang dapat mengakses sistem. Hanya Administrator yang dapat menambah atau mengubah data pengguna.</p>

    <div style="margin-bottom: 20px;">
        <a href="<?= base_url('/users/create') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Tambah Pengguna Baru
        </a>
    </div>

    <div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Lengkap</th>
                    <th>Username (Email)</th>
                    <th>Peran</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="action-buttons">
                                <a href="<?= base_url('/users/edit/' . $user['id']) ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                <button type="submit" form="delete-form-<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                <form id="delete-form-<?php echo $user['id']; ?>" action="<?= base_url('/users/delete/' . $user['id']) ?>" method="POST" style="display: none;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');"></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada pengguna yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
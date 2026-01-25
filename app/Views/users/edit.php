<h1><?php echo $title ?? 'Edit Pengguna'; ?></h1>

<form action="<?= base_url('/users/update/' . $user['id']) ?>" method="POST" style="max-width: 500px;">
    <div style="margin-bottom: 15px;">
        <label for="full_name" style="display: block; margin-bottom: 5px;">Nama Lengkap</label>
        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="username" style="display: block; margin-bottom: 5px;">Username (Email)</label>
        <input type="email" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee;">
        <small style="color: #666;">Username tidak dapat diubah.</small>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="password" style="display: block; margin-bottom: 5px;">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
        <input type="password" id="password" name="password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="password_confirmation" style="display: block; margin-bottom: 5px;">Konfirmasi Password Baru</label>
        <input type="password" id="password_confirmation" name="password_confirmation" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="role" style="display: block; margin-bottom: 5px;">Role</label>
        <select id="role" name="role" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="User" <?php echo ($user['role'] === 'User') ? 'selected' : ''; ?>>User</option>
            <option value="Administrator" <?php echo ($user['role'] === 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
        </select>
    </div>

    <div>
        <button type="submit" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan Perubahan</button>
        <a href="<?= base_url('/users') ?>" style="display: inline-block; margin-left: 10px; color: #333;">Batal</a>
    </div>
</form>
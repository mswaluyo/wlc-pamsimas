<h1><?php echo $title ?? 'Tambah Pengguna Baru'; ?></h1>

<form action="<?= base_url('/users/create') ?>" method="POST" style="max-width: 500px;">
    <div style="margin-bottom: 15px;">
        <label for="full_name" style="display: block; margin-bottom: 5px;">Nama Lengkap</label>
        <input type="text" id="full_name" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="username" style="display: block; margin-bottom: 5px;">Username (Email)</label>
        <input type="email" id="username" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="password" style="display: block; margin-bottom: 5px;">Password</label>
        <input type="password" id="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="password_confirmation" style="display: block; margin-bottom: 5px;">Konfirmasi Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="role" style="display: block; margin-bottom: 5px;">Role</label>
        <select id="role" name="role" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="User">User</option>
            <option value="Administrator">Administrator</option>
        </select>
    </div>

    <div>
        <button type="submit" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan Pengguna</button>
        <a href="<?= base_url('/users') ?>" style="display: inline-block; margin-left: 10px; color: #333;">Batal</a>
    </div>
</form>
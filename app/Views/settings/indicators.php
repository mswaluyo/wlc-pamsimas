<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Atur warna, batas level, dan pilih template tampilan untuk gauge di dashboard. Anda juga bisa mengelola template dari halaman ini.</p>

    <!-- Daftar Template -->
    <h2 style="margin-top: 30px;">Manajemen Template</h2>
    <div style="margin-bottom: 20px;">
        <a href="<?= base_url('/templates/create') ?>" class="btn btn-success"><i class="fas fa-plus-circle"></i> Tambah Template Baru</a>
    </div>

    <div class="template-grid">
        <?php foreach ($templates as $template): ?>
            <div class="template-card <?php echo ($template['id'] == $settings['active_template_id']) ? 'active-template' : ''; ?>">
                <div class="template-preview">
                    <?php if (!$template['is_core']): ?>
                        <a href="<?= base_url('/templates/edit/' . $template['id']) ?>" class="edit-overlay-btn" title="Edit Template">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                    <?php endif; ?>
                    <div class="preview-loader">
                        <div class="spinner"></div>
                    </div>
                    <!-- Menggunakan iframe untuk menampilkan preview langsung dari kode template -->
                    <iframe src="<?= base_url('/api/template-preview/' . $template['id']) ?>" scrolling="no" onload="this.previousElementSibling.style.opacity='0'; setTimeout(() => this.previousElementSibling.style.display='none', 300);"></iframe>
                </div>
                <div class="template-info">
                    <div class="template-header">
                        <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                        <?php if ($template['id'] == $settings['active_template_id']): ?>
                            <span class="status-badge status-online">Aktif</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="template-actions">
                    <button type="button" class="btn btn-sm btn-primary activate-btn" data-template-id="<?php echo $template['id']; ?>" <?php echo ($template['id'] == $settings['active_template_id']) ? 'disabled' : ''; ?> title="<?php echo ($template['id'] == $settings['active_template_id']) ? 'Sedang Digunakan' : 'Aktifkan'; ?>">
                        <i class="fas fa-check"></i>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-secondary preview-btn" data-template-id="<?php echo $template['id']; ?>" title="Lihat Detail">
                        <i class="fas fa-eye"></i>
                    </button>

                    <?php if (!$template['is_core']): ?>
                        <a href="<?= base_url('/templates/edit/' . $template['id']) ?>" class="btn btn-sm btn-info" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="submit" form="delete-form-<?php echo $template['id']; ?>" class="btn btn-sm btn-danger" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                        <form id="delete-form-<?php echo $template['id']; ?>" action="<?= base_url('/templates/delete/' . $template['id']) ?>" method="POST" style="display: none;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini?');"></form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Form Pengaturan Warna dan Batas -->
    <form id="indicator-settings-form" action="<?= base_url('/settings/display') ?>" method="POST">
        <!-- Input tersembunyi untuk menyimpan template aktif -->
        <input type="hidden" name="active_template_id" id="active_template_id" value="<?php echo $settings['active_template_id']; ?>">
        
        <h2 style="margin-top: 40px;">Pengaturan Warna & Batas Level</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Batas Atas (%)</th>
                    <th>Warna</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Rendah (Merah)</strong></td>
                    <td><input type="number" name="threshold_low" value="<?php echo $settings['threshold_low']; ?>" min="1" max="99" required> <small>0% s/d nilai ini</small></td>
                    <td><input type="color" name="color_low" value="<?php echo $settings['color_low']; ?>"></td>
                </tr>
                <tr>
                    <td><strong>Sedang (Kuning)</strong></td>
                    <td><input type="number" name="threshold_medium" value="<?php echo $settings['threshold_medium']; ?>" min="1" max="99" required> <small>Di atas batas rendah s/d nilai ini</small></td>
                    <td><input type="color" name="color_medium" value="<?php echo $settings['color_medium']; ?>"></td>
                </tr>
                <tr>
                    <td><strong>Tinggi (Hijau)</strong></td>
                    <td><small>Di atas batas sedang s/d 100%</small></td>
                    <td><input type="color" name="color_high" value="<?php echo $settings['color_high']; ?>"></td>
                </tr>
            </tbody>
        </table>
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan Warna</button>
            <button type="button" class="btn btn-secondary" onclick="if(confirm('Apakah Anda yakin ingin mereset pengaturan warna dan batas ke default?')) { document.getElementById('reset-settings-form').submit(); }" style="margin-left: 10px;">Reset Default</button>
        </div>
    </form>
    <form id="reset-settings-form" action="<?= base_url('/settings/display/reset') ?>" method="POST" style="display: none;"></form>
</div>

<!-- Modal Preview Template -->
<div id="previewModal" class="modal-overlay">
    <div class="modal-content" style="width: 80%; height: 80%; max-width: 800px; display: flex; flex-direction: column;">
        <span class="modal-close" id="closePreviewModal">&times;</span>
        <h2 style="margin-top: 0;">Preview Template</h2>
        <div style="flex-grow: 1; border: 1px solid #ddd; background: #f4f7f6;">
            <iframe id="previewFrame" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>
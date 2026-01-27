<div class="card">
    <h1><?php echo $title ?? 'Pengaturan Tampilan'; ?></h1>
    <p>Sesuaikan indikator warna, batas level air, dan template gauge yang digunakan pada dashboard.</p>

    <form action="<?= base_url('/settings/display') ?>" method="POST">
        
        <!-- Bagian 1: Pengaturan Warna & Batas -->
        <h2 style="margin-top: 20px; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Konfigurasi Warna & Batas Level</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top: 15px;">
            
            <!-- Level Rendah -->
            <div style="padding: 20px; border: 1px solid #eee; border-radius: 8px; border-left: 5px solid <?php echo $settings['color_low']; ?>; background: #fff;">
                <h3 style="margin-top: 0; font-size: 1.1rem;">Level Rendah / Kritis</h3>
                <div class="form-group">
                    <label>Batas Bawah (%)</label>
                    <input type="number" name="threshold_low" value="<?php echo $settings['threshold_low']; ?>" min="0" max="100" required>
                    <small>Level air di bawah nilai ini dianggap <strong>Rendah</strong>.</small>
                </div>
                <div class="form-group">
                    <label>Warna Indikator</label>
                    <input type="color" name="color_low" value="<?php echo $settings['color_low']; ?>" style="width: 100%; height: 40px; padding: 2px; border-radius: 4px; cursor: pointer;">
                </div>
            </div>

            <!-- Level Sedang -->
            <div style="padding: 20px; border: 1px solid #eee; border-radius: 8px; border-left: 5px solid <?php echo $settings['color_medium']; ?>; background: #fff;">
                <h3 style="margin-top: 0; font-size: 1.1rem;">Level Sedang</h3>
                <div class="form-group">
                    <label>Batas Menengah (%)</label>
                    <input type="number" name="threshold_medium" value="<?php echo $settings['threshold_medium']; ?>" min="0" max="100" required>
                    <small>Level air di atas nilai ini dianggap <strong>Tinggi</strong>.</small>
                </div>
                <div class="form-group">
                    <label>Warna Indikator</label>
                    <input type="color" name="color_medium" value="<?php echo $settings['color_medium']; ?>" style="width: 100%; height: 40px; padding: 2px; border-radius: 4px; cursor: pointer;">
                </div>
            </div>

            <!-- Level Tinggi -->
            <div style="padding: 20px; border: 1px solid #eee; border-radius: 8px; border-left: 5px solid <?php echo $settings['color_high']; ?>; background: #fff;">
                <h3 style="margin-top: 0; font-size: 1.1rem;">Level Tinggi / Aman</h3>
                <div class="form-group">
                    <label>Kondisi Penuh</label>
                    <input type="text" value="> Batas Menengah" disabled style="background: #f9f9f9; color: #666;">
                    <small>Otomatis aktif jika level air di atas batas menengah.</small>
                </div>
                <div class="form-group">
                    <label>Warna Indikator</label>
                    <input type="color" name="color_high" value="<?php echo $settings['color_high']; ?>" style="width: 100%; height: 40px; padding: 2px; border-radius: 4px; cursor: pointer;">
                </div>
            </div>
        </div>

        <!-- Bagian 2: Pilihan Template -->
        <h2 style="margin-top: 30px; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Pilih Template Gauge Dashboard</h2>
        <div class="template-grid">
            <?php foreach ($templates as $tpl): ?>
                <?php $isActive = ($settings['active_template_id'] == $tpl['id']); ?>
                <label class="template-card <?php echo $isActive ? 'active-template' : ''; ?>" style="cursor: pointer;">
                    <div class="template-preview">
                        <!-- Radio button hidden but functional -->
                        <input type="radio" name="active_template_id" value="<?php echo $tpl['id']; ?>" <?php echo $isActive ? 'checked' : ''; ?> style="display: none;">
                        
                        <!-- Iframe Preview -->
                        <iframe src="<?= base_url('/api/template/preview/' . $tpl['id']) ?>" scrolling="no"></iframe>
                        
                        <div class="preview-loader">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    <div class="template-info">
                        <div class="template-header">
                            <h3 class="template-name"><?php echo htmlspecialchars($tpl['name']); ?></h3>
                            <?php if ($isActive): ?>
                                <span class="status-badge status-online"><i class="fas fa-check"></i> Aktif</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size: 0.85rem; color: #666; margin: 5px 0;"><?php echo htmlspecialchars($tpl['description']); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Tombol Aksi di Kanan Bawah -->
        <div class="table-footer-actions" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <a href="<?= base_url('/settings/display/reset') ?>" class="btn btn-info" onclick="return confirm('Reset semua pengaturan tampilan ke default?')" style="margin-right: auto;">
                <i class="fas fa-undo"></i> Reset Default
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
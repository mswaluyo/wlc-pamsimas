<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Buat atau edit template dengan menempelkan kode HTML, CSS, dan JavaScript di bawah ini.</p>

    <form action="<?php echo $form_action ?? base_url('/templates/create'); ?>" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
        <div class="form-group">
            <label for="name">Nama Template</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($template['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi Singkat</label>
            <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($template['description'] ?? ''); ?>" placeholder="Contoh: Gauge modern dengan efek pengisian melingkar.">
        </div>
        
        <div class="form-group">
            <label for="html_code">Kode HTML</label>
            <textarea id="html_code" name="html_code" rows="10" required placeholder="Contoh: <div class='gauge-card'>...</div>"><?php echo htmlspecialchars($template['html_code'] ?? ''); ?></textarea>
            <small>Wajib diisi. Pastikan elemen utama dibungkus dengan <code>&lt;div class="gauge-card"&gt;</code>.</small>
        </div>

        <div class="form-group">
            <label for="css_code">Kode CSS</label>
            <textarea id="css_code" name="css_code" rows="10" required placeholder="Contoh: .gauge-card { ... }"><?php echo htmlspecialchars($template['css_code'] ?? ''); ?></textarea>
            <small>Wajib diisi. Semua selector harus diawali dengan <code>.gauge-card</code> untuk menghindari konflik.</small>
        </div>

        <div class="form-group">
            <label for="js_code">Kode JavaScript (Opsional)</label>
            <textarea id="js_code" name="js_code" rows="10" placeholder="Contoh: function initGauge(card) { ... }"><?php echo htmlspecialchars($template['js_code'] ?? ''); ?></textarea>
            <small>Kosongkan jika tidak perlu. Jika diisi, harus menyediakan fungsi <code>initGauge(card)</code> dan <code>updateGauge(card, level, color)</code>.</small>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan Template</button>
            <a href="<?= base_url('/templates') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
<div class="card">
    <h1><?php echo $title ?? 'Form Tangki'; ?></h1>

    <form action="<?php echo $form_action; ?>" method="POST" style="max-width: 500px;">
        <div style="margin-bottom: 15px;">
            <label for="tank_name" style="display: block; margin-bottom: 5px;">Nama Tangki</label>
            <input type="text" id="tank_name" name="tank_name" value="<?php echo htmlspecialchars($tank['tank_name'] ?? ''); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="height" style="display: block; margin-bottom: 5px;">Tinggi Tangki (cm)</label>
            <input type="number" step="0.01" id="height" name="height" value="<?php echo htmlspecialchars($tank['height'] ?? ''); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="tank_shape" style="display: block; margin-bottom: 5px;">Bentuk Tangki</label>
            <select id="tank_shape" name="tank_shape" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="kotak" <?php echo (($tank['tank_shape'] ?? 'kotak') === 'kotak') ? 'selected' : ''; ?>>Kotak</option>
                <option value="bulat" <?php echo (($tank['tank_shape'] ?? '') === 'bulat') ? 'selected' : ''; ?>>Bulat</option>
            </select>
        </div>

        <!-- Dimensi untuk Tangki Kotak -->
        <div id="dimensi_kotak" style="margin-bottom: 15px;">
            <div style="margin-bottom: 15px;">
                <label for="length" style="display: block; margin-bottom: 5px;">Panjang (cm)</label>
                <input type="number" step="0.01" id="length" name="length" value="<?php echo htmlspecialchars($tank['length'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="width" style="display: block; margin-bottom: 5px;">Lebar (cm)</label>
                <input type="number" step="0.01" id="width" name="width" value="<?php echo htmlspecialchars($tank['width'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <!-- Dimensi untuk Tangki Bulat -->
        <div id="dimensi_bulat" style="margin-bottom: 15px; display: none;">
            <div style="margin-bottom: 15px;">
                <label for="diameter" style="display: block; margin-bottom: 5px;">Diameter (cm)</label>
                <input type="number" step="0.01" id="diameter" name="diameter" value="<?php echo htmlspecialchars($tank['diameter'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <div>
            <button type="submit" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan</button>
            <a href="<?= base_url('/settings/tanks') ?>" style="display: inline-block; margin-left: 10px; color: #333;">Batal</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shapeSelect = document.getElementById('tank_shape');
            const dimensiKotak = document.getElementById('dimensi_kotak');
            const dimensiBulat = document.getElementById('dimensi_bulat');

            function toggleDimensions() {
                if (shapeSelect.value === 'kotak') {
                    dimensiKotak.style.display = 'block';
                    dimensiBulat.style.display = 'none';
                } else {
                    dimensiKotak.style.display = 'none';
                    dimensiBulat.style.display = 'block';
                }
            }

            // Panggil saat halaman dimuat
            toggleDimensions();

            // Panggil saat pilihan berubah
            shapeSelect.addEventListener('change', toggleDimensions);
        });
    </script>
</div>
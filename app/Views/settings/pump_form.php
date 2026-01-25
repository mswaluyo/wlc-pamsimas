<div class="card">
    <h1><?php echo $title ?? 'Form Pompa'; ?></h1>

    <form action="<?php echo $form_action; ?>" method="POST" style="max-width: 500px;">
        <div style="margin-bottom: 15px;">
            <label for="pump_name" style="display: block; margin-bottom: 5px;">Nama Pompa</label>
            <input type="text" id="pump_name" name="pump_name" value="<?php echo htmlspecialchars($pump['pump_name'] ?? ''); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="flow_rate_lps" style="display: block; margin-bottom: 5px;">Debit (Liter/detik)</label>
            <input type="number" step="0.01" id="flow_rate_lps" name="flow_rate_lps" value="<?php echo htmlspecialchars($pump['flow_rate_lps'] ?? '0'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="power_watt" style="display: block; margin-bottom: 5px;">Daya (Watt)</label>
            <input type="number" id="power_watt" name="power_watt" value="<?php echo htmlspecialchars($pump['power_watt'] ?? '0'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="delay_seconds" style="display: block; margin-bottom: 5px;">Waktu Tunda Air Sampai (detik)</label>
            <input type="number" id="delay_seconds" name="delay_seconds" value="<?php echo htmlspecialchars($pump['delay_seconds'] ?? '0'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div>
            <button type="submit" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan</button>
            <a href="<?= base_url('/settings/pumps') ?>" style="display: inline-block; margin-left: 10px; color: #333;">Batal</a>
        </div>
    </form>
</div>
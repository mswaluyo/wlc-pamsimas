<h1><?php echo $title ?? 'Form Sensor'; ?></h1>

<form action="<?php echo $form_action; ?>" method="POST" style="max-width: 500px;">
    <div style="margin-bottom: 15px;">
        <label for="sensor_name" style="display: block; margin-bottom: 5px;">Nama Sensor</label>
        <input type="text" id="sensor_name" name="sensor_name" value="<?php echo htmlspecialchars($sensor['sensor_name'] ?? ''); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="sensor_type" style="display: block; margin-bottom: 5px;">Tipe Sensor</label>
        <input type="text" id="sensor_type" name="sensor_type" value="<?php echo htmlspecialchars($sensor['sensor_type'] ?? 'HC-SR04'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="full_tank_distance" style="display: block; margin-bottom: 5px;">Jarak Tangki Penuh (cm)</label>
        <input type="number" step="0.1" id="full_tank_distance" name="full_tank_distance" value="<?php echo htmlspecialchars($sensor['full_tank_distance'] ?? '10'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trigger_percentage" style="display: block; margin-bottom: 5px;">Pemicu Pompa (%)</label>
        <input type="number" id="trigger_percentage" name="trigger_percentage" value="<?php echo htmlspecialchars($sensor['trigger_percentage'] ?? '70'); ?>" required min="0" max="100" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

    <div>
        <button type="submit" style="background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Simpan</button>
        <a href="<?= base_url('/settings/sensors') ?>" style="display: inline-block; margin-left: 10px; color: #333;">Batal</a>
    </div>
</form>
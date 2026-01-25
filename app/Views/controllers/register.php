<div class="card">
    <h1><?php echo $title ?? 'Daftarkan Perangkat Baru'; ?></h1>

    <form action="<?= base_url('/controllers/register') ?>" method="POST" style="max-width: 600px;">
        <div class="form-group">
            <label for="mac_address">MAC Address Perangkat</label>
            <input type="text" id="mac_address" name="mac_address" value="<?php echo htmlspecialchars($mac_address ?? ''); ?>" required placeholder="Contoh: A0:20:A6:...">
            <small>Masukkan MAC Address yang tertera pada perangkat atau hasil deteksi otomatis.</small>
        </div>

        <div class="form-group">
            <label for="tank_id">Hubungkan dengan Tangki</label>
            <select id="tank_id" name="tank_id" required>
                <option value="">-- Pilih Tangki --</option>
                <?php foreach ($tanks as $tank): ?>
                    <option value="<?php echo $tank['id']; ?>"><?php echo htmlspecialchars($tank['tank_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pump_id">Hubungkan dengan Pompa</label>
            <select id="pump_id" name="pump_id" required>
                <option value="">-- Pilih Pompa --</option>
                <?php foreach ($pumps as $pump): ?>
                    <option value="<?php echo $pump['id']; ?>"><?php echo htmlspecialchars($pump['pump_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sensor_id">Hubungkan dengan Sensor</label>
            <select id="sensor_id" name="sensor_id" required>
                <option value="">-- Pilih Sensor --</option>
                <?php foreach ($sensors as $sensor): ?>
                    <option value="<?php echo $sensor['id']; ?>"><?php echo htmlspecialchars($sensor['sensor_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan & Daftarkan</button>
            <a href="<?= base_url('/controllers') ?>" class="btn btn-secondary" style="margin-left: 10px;">Batal</a>
        </div>
    </form>
</div>
<div class="gauge-card">
    <div class="gauge-title"><?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?></div>
    <div class="tank-gauge-container">
        <!-- Elemen air yang tingginya akan diubah oleh JS -->
        <div class="tank-gauge-water" id="tank-water-<?php echo $controller['id']; ?>"></div>
        <!-- Teks persentase -->
        <div class="tank-gauge-text" id="tank-text-<?php echo $controller['id']; ?>">0%</div>
    </div>
</div>
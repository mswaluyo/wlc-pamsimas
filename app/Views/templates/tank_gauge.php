<div class="gauge-card">
    <div class="gauge-title"><?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?></div>
    <div class="tank-gauge-container" style="position: relative;">
        <!-- Elemen air yang tingginya akan diubah oleh JS -->
        <div class="tank-gauge-water" id="tank-water-<?php echo $controller['id']; ?>"></div>
        
        <!-- Marker Batas Pemicu (Garis Putus-putus) -->
        <div id="tank-trigger-<?php echo $controller['id']; ?>" 
             style="position: absolute; left: 0; right: 0; height: 0; border-top: 2px dashed #555; z-index: 5; bottom: <?php echo $controller['trigger_percentage']; ?>%;"
             title="Batas Nyala Otomatis: <?php echo $controller['trigger_percentage']; ?>%"></div>
             
        <!-- Teks persentase -->
        <div class="tank-gauge-text" id="tank-text-<?php echo $controller['id']; ?>">0%</div>
    </div>
</div>
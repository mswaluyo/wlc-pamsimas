<div class="gauge-card">
    <div class="gauge-title"><?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?></div>
    <div class="simple-bar-gauge-container" style="position: relative;">
        <!-- Marker Batas Pemicu (Garis Hitam Kecil) -->
        <div id="bar-trigger-<?php echo $controller['id']; ?>" 
             style="position: absolute; top: 0; bottom: 0; width: 2px; background-color: #333; z-index: 5; left: <?php echo $controller['trigger_percentage']; ?>%; opacity: 0.7;"
             title="Batas Nyala Otomatis: <?php echo $controller['trigger_percentage']; ?>%"></div>
             
        <div class="simple-bar-gauge-fill" id="bar-fill-<?php echo $controller['id']; ?>"></div>
        <div class="simple-bar-gauge-text" id="bar-text-<?php echo $controller['id']; ?>">0%</div>
    </div>
</div>
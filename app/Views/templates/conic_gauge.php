<div class="gauge-card">
    <div class="gauge-title"><?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?></div>
    <div class="conic-gauge" id="gauge-body-<?php echo $controller['id']; ?>">
        <div class="gauge-text-overlay">
            <span class="value" id="gauge-value-<?php echo $controller['id']; ?>">0</span>%
        </div>
    </div>
</div>
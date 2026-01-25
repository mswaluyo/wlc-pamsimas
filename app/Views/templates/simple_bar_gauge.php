<div class="gauge-card">
    <div class="gauge-title"><?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?></div>
    <div class="simple-bar-gauge-container">
        <div class="simple-bar-gauge-fill" id="bar-fill-<?php echo $controller['id']; ?>"></div>
        <div class="simple-bar-gauge-text" id="bar-text-<?php echo $controller['id']; ?>">0%</div>
    </div>
</div>
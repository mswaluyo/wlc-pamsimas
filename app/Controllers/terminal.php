<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Monitoring log sistem dan kejadian perangkat secara real-time.</p>

    <!-- Terminal Monitoring Section -->
    <div class="terminal-container">
        <div class="terminal-header">
            <div class="terminal-title">
                <i class="fas fa-terminal" style="margin-right:8px;"></i> Live System Events
            </div>
            <div class="terminal-status">
                <span id="terminal-status-dot" class="status-dot"></span>
                <span id="terminal-status-text">Connecting...</span>
            </div>
        </div>
        <div id="terminal-window" class="terminal-window">
            <div style="padding:10px; color:#888;">Memuat data...</div>
        </div>
    </div>
</div>
<div class="card">
    <h1><?php echo $title; ?></h1>
    <p>Monitoring log sistem dan kejadian perangkat secara real-time.</p>

    <!-- Terminal Monitoring Section -->
    <div class="terminal-container">
        <div class="terminal-header">
            <div class="terminal-title">
                <i class="fas fa-terminal" style="margin-right:8px;"></i> Live System Events
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button id="clear-terminal-btn" style="background:none; border:none; color:#ccc; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; transition: color 0.2s;" title="Bersihkan Layar" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ccc'">
                    <i class="fas fa-trash-alt" style="margin-right:5px;"></i> Clear
                </button>
                <div style="width: 1px; height: 15px; background-color: #444;"></div>
                <div style="display: flex; align-items: center; font-size: 0.85rem; color: #ccc;">
                    <input type="checkbox" id="auto-scroll-toggle" checked style="margin-right: 5px; cursor: pointer;">
                    <label for="auto-scroll-toggle" style="cursor: pointer; margin: 0; user-select: none;">Auto Scroll</label>
                </div>
                <div class="terminal-status">
                    <span id="terminal-status-dot" class="status-dot"></span>
                    <span id="terminal-status-text">Connecting...</span>
                </div>
            </div>
        </div>
        <div id="terminal-window" class="terminal-window">
            <div style="padding:10px; color:#888;">Memuat data...</div>
        </div>
    </div>
</div>
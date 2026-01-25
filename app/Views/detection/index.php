<div class="card">
    <h1><?php echo $title ?? 'Deteksi Perangkat'; ?></h1>
    <p class="page-description">Temukan perangkat baru yang online dan siap untuk didaftarkan ke dalam sistem.</p>
    
    <div class="scan-container">
        <div class="scan-icon-wrapper">
            <i class="fas fa-wifi scan-icon"></i>
            <div class="scan-ripple"></div>
        </div>
        
        <button id="scan-button" class="btn btn-primary btn-lg">
            <i class="fas fa-search"></i> Mulai Deteksi
        </button>
        
        <p id="scan-status">Klik tombol untuk memulai pencarian perangkat...</p>
        
        <div id="loading-spinner" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Sedang memindai jaringan...
        </div>
    </div>

    <div id="device-list" class="device-list">
        <!-- Hasil deteksi akan muncul di sini via JavaScript -->
    </div>
</div>
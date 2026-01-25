document.addEventListener('DOMContentLoaded', function() {
    // Temukan form utama di halaman
    const settingsForm = document.getElementById('indicator-settings-form');
    if (!settingsForm) return;

    // Temukan input tersembunyi yang menyimpan ID template aktif
    const activeTemplateInput = document.getElementById('active_template_id');

    // Tambahkan event listener ke semua tombol 'Aktifkan'
    document.querySelectorAll('.activate-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            
            // 1. Perbarui nilai input tersembunyi dengan ID template yang baru
            activeTemplateInput.value = templateId;
            // 2. Kirim form untuk menyimpan semua pengaturan (termasuk template aktif yang baru)
            settingsForm.submit();
        });
    });

    // --- Logika Preview Template ---
    const previewModal = document.getElementById('previewModal');
    const closePreviewBtn = document.getElementById('closePreviewModal');
    const previewFrame = document.getElementById('previewFrame');

    if (previewModal && closePreviewBtn && previewFrame) {
        document.querySelectorAll('.preview-btn').forEach(button => {
            button.addEventListener('click', function() {
                const templateId = this.getAttribute('data-template-id');
                // Menggunakan BASE_URL global yang didefinisikan di main.php
                previewFrame.src = `${BASE_URL}api/template-preview/${templateId}`;
                previewModal.style.display = 'flex';
            });
        });

        closePreviewBtn.addEventListener('click', function() {
            previewModal.style.display = 'none';
            previewFrame.src = 'about:blank'; // Reset iframe
        });

        window.addEventListener('click', function(event) {
            if (event.target === previewModal) {
                previewModal.style.display = 'none';
                previewFrame.src = 'about:blank';
            }
        });
    }
});
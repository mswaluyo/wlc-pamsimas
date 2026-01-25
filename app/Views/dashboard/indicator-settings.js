document.addEventListener('DOMContentLoaded', function() {
    // Temukan form utama di halaman
    const settingsForm = document.querySelector('form[action$="/settings/display"]');
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
});

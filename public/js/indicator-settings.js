document.addEventListener('DOMContentLoaded', function() {
    // Handle iframe loading state (Menghilangkan spinner saat iframe selesai dimuat)
    const iframes = document.querySelectorAll('.template-preview iframe');
    iframes.forEach(iframe => {
        iframe.onload = function() {
            const loader = this.parentElement.querySelector('.preview-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 300);
            }
        };
    });

    // Handle template selection visual feedback (Efek klik pada kartu template)
    const radioInputs = document.querySelectorAll('input[name="active_template_id"]');
    radioInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Hapus kelas aktif dari semua kartu
            document.querySelectorAll('.template-card').forEach(card => {
                card.classList.remove('active-template');
                const badge = card.querySelector('.status-badge');
                if(badge) badge.remove();
            });
            
            // Tambahkan kelas aktif ke kartu yang dipilih
            const selectedCard = this.closest('.template-card');
            selectedCard.classList.add('active-template');
            
            // Tambahkan badge 'Aktif' secara dinamis
            const header = selectedCard.querySelector('.template-header');
            if (header && !header.querySelector('.status-badge')) {
                const badge = document.createElement('span');
                badge.className = 'status-badge status-online';
                badge.innerHTML = '<i class="fas fa-check"></i> Aktif';
                header.appendChild(badge);
            }
        });
    });
});
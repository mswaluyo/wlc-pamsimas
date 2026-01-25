<div class="card">
    <h1><?php echo $title ?? 'Manajemen Template'; ?></h1>
    <p>Kelola tampilan visual untuk gauge di dashboard. Template bawaan tidak dapat diubah atau dihapus.</p>

    <div style="margin-bottom: 20px;">
        <a href="/wlc/templates/create" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Tambah Template Baru
        </a>
    </div>

    <div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Nama Template</th>
                    <th>Deskripsi</th>
                    <th style="width: 20%;">Preview</th>
                    <th style="width: 15%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($templates)): ?>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                        <td><?php echo htmlspecialchars($template['name']); ?></td>
                            <td><?php echo htmlspecialchars($template['description']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-secondary preview-btn" data-template-id="<?php echo $template['id']; ?>">Lihat Preview</button>
                            </td>
                            <td class="action-buttons">
                                <?php if (!$template['is_core']): ?>
                                    <a href="/wlc/templates/edit/<?php echo $template['id']; ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form action="/wlc/templates/delete/<?php echo $template['id']; ?>" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini?');">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--dark-gray);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Belum ada template yang dibuat.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Struktur HTML untuk Modal Preview (disembunyikan secara default) -->
<div id="preview-modal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" id="modal-close-btn">&times;</span>
        <h3 id="modal-title">Preview Template</h3>
        <div id="modal-body" style="width: 600px; height: 450px; border: 1px solid #ccc;">
            <iframe id="preview-iframe" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('preview-modal');
    const closeBtn = document.getElementById('modal-close-btn');
    const iframe = document.getElementById('preview-iframe');

    document.querySelectorAll('.preview-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            // Set sumber iframe ke endpoint API baru
            iframe.src = `/wlc/api/template-preview/${templateId}`;
            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
        iframe.src = 'about:blank'; // Kosongkan iframe saat ditutup
    }

    closeBtn.onclick = closeModal;
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
});
</script>
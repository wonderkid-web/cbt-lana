/**
 * sweetalert_delete.js
 * Shared SweetAlert2 confirmation helper untuk semua halaman admin.
 * Include file ini SETELAH SweetAlert2 CDN di setiap halaman admin.
 */

// ===== KONFIRMASI HAPUS DATA =====
function confirmHapus(nama, url) {
    Swal.fire({
        title: 'Hapus Data?',
        html: 'Anda akan menghapus <strong>' + nama + '</strong>.<br>Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

// ===== KONFIRMASI AKSI LAIN (Non-Delete) =====
function confirmAksi(title, message, url, confirmText, confirmColor) {
    Swal.fire({
        title: title || 'Konfirmasi',
        html: message || 'Lanjutkan aksi ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor || '#3085d6',
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmText || 'Ya',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

// ===== AUTO-BIND: Elemen dengan class "btn-hapus" =====
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-hapus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var nama = this.getAttribute('data-nama') || 'data ini';
            var url  = this.getAttribute('href') || this.getAttribute('data-url') || '';
            confirmHapus(nama, url);
        });
    });
});
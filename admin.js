/**
 * KRISUKA Admin Dashboard - Interactivity Script
 */

document.addEventListener('DOMContentLoaded', () => {
  // Toggle Mobile Sidebar
  const toggleBtn = document.getElementById('mobileMenuToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  // Preview Uploaded Image
  const imageInput = document.getElementById('productImageInput');
  const previewImg = document.getElementById('imagePreview');
  if (imageInput && previewImg) {
    imageInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }
});

// Konfirmasi Hapus Data
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
  return confirm(message);
}


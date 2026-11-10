<?php
if (!defined('KRISUKA_APP')) {
    exit;
}

$storeWa = get_setting('whatsapp_number', '6282274761719');
$storeName = get_setting('store_name', 'KRISUKA');
$storeTagline = get_setting('store_tagline', 'Renyah, Lezat, Cita Rasa Lokal.');
$storeAddress = get_setting('store_address', 'Fakfak, Papua Barat, Indonesia');
$instagramUrl = get_setting('instagram_url', 'https://www.instagram.com/krisuka.fakfak?utm_source=ig_web_button_share_sheet&cplk=ZDNlZDc0MzIxNw==');
$instagramHandle = get_setting('instagram_handle', '@krisuka.fakfak');
$flash = get_flash();
?>
  <!-- Site Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand Info -->
        <div class="footer-col">
          <div class="footer-brand-title"><?= e($storeName) ?></div>
          <div class="footer-brand-slogan">"<?= e($storeTagline) ?>"</div>
          <p class="footer-brand-desc">
            Brand keripik lokal dari Fakfak, Papua Barat. Menghadirkan olahan terbaik buah sukun, keladi, dan pisang lokal dengan bumbu istimewa yang higienis, lezat, dan super renyah.
          </p>
        </div>

        <!-- Quick Navigation -->
        <div class="footer-col">
          <h4 class="footer-col-title">Navigasi Cepat</h4>
          <ul class="footer-links-list">
            <li><a href="<?= base_url('index.php') ?>">Beranda</a></li>
            <li><a href="<?= base_url('products.php') ?>">Katalog Produk</a></li>
            <li><a href="<?= base_url('about.php') ?>">Cerita Tentang Kami</a></li>
            <li><a href="<?= base_url('contact.php') ?>">Hubungi Kami</a></li>
            <li><a href="<?= base_url('order-status.php') ?>">Lacak Status Pesanan</a></li>
          </ul>
        </div>

        <!-- Contact & Legal -->
        <div class="footer-col">
          <h4 class="footer-col-title">Informasi Usaha</h4>
          <div class="footer-info-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span><?= e($storeAddress) ?></span>
          </div>

          <div class="footer-info-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            <a href="https://wa.me/<?= e($storeWa) ?>" target="_blank" rel="noopener noreferrer">
              WhatsApp: +<?= e($storeWa) ?>
            </a>
          </div>

          <div class="footer-info-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
            </svg>
            <a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener noreferrer">
              Instagram: <?= e($instagramHandle) ?>
            </a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>
          &copy; <?= date('Y') ?> <strong><?= e($storeName) ?> (Keripik Sukun dan Keladi Fakfak)</strong>. Hak Cipta Dilindungi.
        </div>
        <div>
          <a href="<?= base_url('privacy.php') ?>">Kebijakan Privasi</a> &bull; 
          <a href="<?= base_url('terms.php') ?>">Syarat &amp; Ketentuan</a> &bull;
          <a href="<?= base_url('admin/login.php') ?>" style="opacity: 0.6;">Portal Admin</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Back to Top Button -->
  <button class="back-to-top-btn" id="backToTopBtn" aria-label="Kembali ke atas">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <!-- Floating WhatsApp Pill Button -->
  <a href="https://wa.me/<?= e($storeWa) ?>?text=<?= urlencode('Halo ' . $storeName . ', saya ingin bertanya mengenai pemesanan keripik lokal khas Fakfak.') ?>" 
     class="floating-wa-btn" 
     target="_blank" 
     rel="noopener noreferrer" 
     aria-label="Chat WhatsApp KRISUKA">
    <svg viewBox="0 0 24 24" fill="currentColor">
      <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
    </svg>
    <span>Chat WhatsApp</span>
  </a>

  <!-- Mobile Bottom Navigation Bar (Thumb-friendly Single-hand Nav) -->
  <?php 
  $cTotals = cart_totals(); 
  $navCurrent = $activeNav ?? 'home';
  ?>
  <nav class="mobile-bottom-nav" aria-label="Navigasi Bawah Ponsel">
    <a href="<?= base_url('index.php') ?>" class="mobile-nav-item <?= ($navCurrent === 'home') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
      </svg>
      <span>Beranda</span>
    </a>

    <a href="<?= base_url('products.php') ?>" class="mobile-nav-item <?= ($navCurrent === 'products') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
        <line x1="8" y1="21" x2="16" y2="21"></line>
        <line x1="12" y1="17" x2="12" y2="21"></line>
      </svg>
      <span>Katalog</span>
    </a>

    <a href="<?= base_url('cart.php') ?>" class="mobile-nav-item <?= ($navCurrent === 'cart') ? 'active' : '' ?>" style="position: relative;">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
      </svg>
      <span>Keranjang</span>
      <?php if ((int)$cTotals['total_qty'] > 0): ?>
        <span class="mobile-nav-badge" id="mobileCartBadge"><?= (int)$cTotals['total_qty'] ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= base_url('order-status.php') ?>" class="mobile-nav-item <?= ($navCurrent === 'order-status') ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <span>Lacak</span>
    </a>

    <a href="https://wa.me/<?= e($storeWa) ?>?text=<?= urlencode('Halo ' . $storeName . ', saya ingin bertanya mengenai pemesanan keripik lokal khas Fakfak.') ?>" 
       class="mobile-nav-item" 
       target="_blank" 
       rel="noopener noreferrer" 
       style="color: #15803D;">
      <svg viewBox="0 0 24 24" fill="currentColor" stroke="none">
        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
      </svg>
      <span>WhatsApp</span>
    </a>
  </nav>

  <!-- Main App Script -->
  <script src="<?= asset_url('js/app.js') ?>"></script>

  <?php if ($flash): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      showToast(<?= json_encode($flash['message']) ?>, '<?= ($flash['type'] === 'success') ? '✅' : '⚠️' ?>');
    });
  </script>
  <?php endif; ?>
</body>
</html>


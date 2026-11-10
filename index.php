<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'home';
$pageTitle = 'KRISUKA | Keripik Sukun, Keladi & Pisang Khas Fakfak, Papua Barat';

// Ambil produk unggulan dari database
$featuredStmt = db()->query("SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 
    ORDER BY p.is_featured DESC, p.id ASC 
    LIMIT 6");
$featuredProducts = $featuredStmt->fetchAll();

// Ambil semua kategori
$categories = db()->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Ambil ulasan & testimoni pembeli terbaru
$reviewsStmt = db()->query("SELECT r.*, p.name as product_name, p.image as product_image 
    FROM product_reviews r 
    JOIN products p ON r.product_id = p.id 
    WHERE r.is_approved = 1 
    ORDER BY r.created_at DESC 
    LIMIT 3");
$homeReviews = $reviewsStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section" id="beranda">
  <div class="container">
    <div class="hero-grid">
      <!-- Left Content -->
      <div class="hero-content">
        <div class="hero-badge-tag">
          <span class="dot"></span>
          Oleh-Oleh Khas Fakfak, Papua Barat
        </div>

        <h1 class="hero-title">
          Nikmati Renyahnya Keripik <span class="highlight-gold">KRISUKA</span>
        </h1>

        <p class="hero-subtitle">
          Keaslian rasa buah <strong>Sukun</strong>, umbi <strong>Keladi</strong>, dan <strong>Pisang</strong> lokal pilihan hasil alam Kabupaten Fakfak. Diiris tipis, digoreng renyah, dan dibalut aneka bumbu istimewa yang memanjakan lidah.
        </p>

        <div class="hero-cta-group">
          <a href="<?= base_url('products.php') ?>" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            Belanja Sekarang
          </a>
          <a href="<?= base_url('about.php') ?>" class="btn btn-secondary">
            Cerita KRISUKA
          </a>
        </div>

        <div class="hero-features-list">
          <div class="hero-feature-item">
            <div class="feature-icon-circle">🌴</div>
            <div class="feature-text-block">
              <strong>Sukun &amp; Keladi Asli</strong>
              <span>Panen Segar Petani Fakfak</span>
            </div>
          </div>
          <div class="hero-feature-item">
            <div class="feature-icon-circle">✨</div>
            <div class="feature-text-block">
              <strong>Ekstra Renyah</strong>
              <span>Garing, Gurih &amp; Tidak Berminyak</span>
            </div>
          </div>
          <div class="hero-feature-item">
            <div class="feature-icon-circle">💬</div>
            <div class="feature-text-block">
              <strong>Pesan Cepat WhatsApp</strong>
              <span>Praktis &amp; Terverifikasi Aman</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Visual -->
      <div class="hero-visual-card">
        <div class="hero-top-badge">
          ⭐ 7 Varian Favorit Tersedia
        </div>

        <div class="hero-main-img-box">
          <img 
            src="https://images.unsplash.com/photo-1621447504864-d8686e12698c?auto=format&fit=crop&w=800&q=80" 
            alt="Aneka Keripik Lokal KRISUKA Fakfak Papua Barat"
            loading="eager"
            onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'600\' viewBox=\'0 0 800 600\'><rect width=\'100%\' height=\'100%\' fill=\'%231B4D3E\'/><text x=\'50%\' y=\'45%\' fill=\'%23D4AF37\' font-size=\'44\' font-family=\'serif\' font-weight=\'bold\' text-anchor=\'middle\'>KRISUKA</text><text x=\'50%\' y=\'55%\' fill=\'%23FAF7F0\' font-size=\'22\' font-family=\'sans-serif\' text-anchor=\'middle\'>Keripik Sukun, Keladi &amp; Pisang Fakfak</text></svg>';"
          >
        </div>

        <div class="hero-floating-badge">
          <span class="floating-badge-icon">🌿</span>
          <div class="floating-badge-text">
            <strong>100% Cita Rasa Lokal Fakfak</strong>
            <small>Renyah, Lezat &amp; Bikin Nagih</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Value Pillars -->
<section class="section-pillars" aria-label="Keunggulan KRISUKA">
  <div class="container">
    <div class="pillars-grid">
      <div class="pillar-card">
        <div class="pillar-icon-box">🌱</div>
        <div class="pillar-text">
          <h4>Bahan Baku Segar Fakfak</h4>
          <p>Buah sukun, umbi keladi, dan pisang segar dipanen langsung dari kebun warga lokal Fakfak.</p>
        </div>
      </div>

      <div class="pillar-card">
        <div class="pillar-icon-box">🔥</div>
        <div class="pillar-text">
          <h4>Renyah Maksimal &amp; Bersih</h4>
          <p>Tekstur tipis renyah digoreng dengan minyak berkualitas dan standar dapur higienis.</p>
        </div>
      </div>

      <div class="pillar-card">
        <div class="pillar-icon-box">🧂</div>
        <div class="pillar-text">
          <h4>Bumbu Istimewa Melimpah</h4>
          <p>Racikan rasa gurih mentega, rempah balado, jagung bakar, hingga lelehan coklat legit.</p>
        </div>
      </div>

      <div class="pillar-card">
        <div class="pillar-icon-box">📦</div>
        <div class="pillar-text">
          <h4>Siap Kirim ke Mana Saja</h4>
          <p>Kemasan kedap udara dipacking kardus aman untuk oleh-oleh dalam &amp; luar kota.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Products Section -->
<section class="section-products" id="produk">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Paling Banyak Dicari</span>
      <h2 class="section-title">Produk Unggulan KRISUKA</h2>
      <p class="section-desc">Nikmati kelezatan varian terlaris keripik sukun, keladi, dan pisang aneka rasa khas Fakfak untuk camilan santai keluarga.</p>
    </div>

    <!-- Bundling Banner Card -->
    <div class="bundling-banner-card">
      <div class="bundling-content">
        <span class="bundling-badge">🔥 Paket Hemat Oleh-Oleh</span>
        <h3 class="bundling-title">Paket Komplit Aneka Rasa Fakfak</h3>
        <p class="bundling-desc">
          Mau cicipi semua varian ikonik kami? Dapatkan paket hemat lengkap berisi varian Sukun, Keladi, dan Pisang aneka rasa favorit dalam satu kali pesan!
        </p>
      </div>
      <div class="bundling-action-box">
        <span class="bundling-price-total">Mulai <?= format_rupiah(10000) ?> / bks</span>
        <a href="<?= base_url('products.php') ?>" class="btn-bundling">
          Lihat Semua Varian
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </a>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
      <?php foreach ($featuredProducts as $prod): ?>
      <article class="product-card">
        <a href="<?= base_url('product-detail.php?id=' . $prod['id']) ?>" class="product-thumb-container">
          <span class="product-badge-flavor"><?= e($prod['badge']) ?></span>
          <img 
            src="<?= e(product_image_url($prod['image'])) ?>" 
            alt="<?= e($prod['name']) ?> KRISUKA" 
            class="product-thumb-img"
            loading="lazy"
            onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'600\' height=\'400\' viewBox=\'0 0 600 400\'><rect width=\'100%\' height=\'100%\' fill=\'%231B4D3E\'/><text x=\'50%\' y=\'50%\' fill=\'%23FAF7F0\' font-size=\'22\' font-weight=\'bold\' text-anchor=\'middle\'><?= urlencode($prod['name']) ?></text></svg>';"
          >
          <span class="product-netto-tag">⚖️ <?= e($prod['netto']) ?></span>
          <span class="product-stock-tag <?= ($prod['stock'] <= 10) ? 'low' : '' ?>">
            Stok: <?= (int)$prod['stock'] ?>
          </span>
        </a>

        <div class="product-details">
          <h3 class="product-title">
            <a href="<?= base_url('product-detail.php?id=' . $prod['id']) ?>">
              <?= e($prod['name']) ?>
            </a>
          </h3>

          <?php $rStats = get_product_rating_stats((int)$prod['id']); ?>
          <div style="display: flex; align-items: center; gap: 6px; margin: 4px 0 8px; font-size: 0.82rem;">
            <?= $rStats['stars_html'] ?>
            <span style="font-weight: 700; color: var(--color-primary-dark);"><?= number_format($rStats['rating'], 1) ?></span>
            <span style="color: var(--color-text-muted); font-size: 0.78rem;">(<?= $rStats['count'] ?>)</span>
          </div>
          <p class="product-description">
            <?= e(mb_strimwidth($prod['description'], 0, 115, '...')) ?>
          </p>

          <div class="product-pricing-row">
            <span class="product-price-label">Harga per kemasan:</span>
            <span class="product-price-value"><?= format_rupiah($prod['price']) ?></span>
          </div>

          <div class="product-actions">
            <form action="<?= base_url('cart-action.php') ?>" method="POST" style="display:flex; width: 100%; gap: 8px;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">

              <div class="qty-counter">
                <button type="button" class="qty-btn" onclick="stepCardQty(<?= $prod['id'] ?>, -1)">-</button>
                <input type="text" id="qty-input-<?= $prod['id'] ?>" name="quantity" class="qty-input" value="1" readonly>
                <button type="button" class="qty-btn" onclick="stepCardQty(<?= $prod['id'] ?>, 1)">+</button>
              </div>

              <button type="submit" class="add-to-cart-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Keranjang</span>
              </button>

              <a href="<?= base_url('product-detail.php?id=' . $prod['id']) ?>" class="btn-card-detail">
                Detail
              </a>
            </form>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 40px;">
      <a href="<?= base_url('products.php') ?>" class="btn btn-secondary">
        Lihat Seluruh Katalog (7 Varian) &rarr;
      </a>
    </div>
  </div>
</section>

<!-- About Us Snippet Section -->
<section class="section-about" id="tentang">
  <div class="container">
    <div class="about-grid">
      <div class="about-image-wrapper">
        <div class="about-gold-frame"></div>
        <img 
          src="https://images.unsplash.com/photo-1528825871115-3581a5387919?auto=format&fit=crop&w=800&q=80" 
          alt="Hasil Alam Fakfak Papua Barat" 
          class="about-main-img"
          loading="lazy"
        >
      </div>

      <div class="about-content-card">
        <span class="section-tag">Cerita di Balik Renyahnya</span>
        <h2>Mengenalkan Kelezatan Sukun, Keladi &amp; Pisang Fakfak</h2>
        <p>
          <strong>KRISUKA (Keripik Sukun dan Keladi)</strong> didirikan di Fakfak, Papua Barat dengan misi mengangkat potensi komoditas pangan lokal berkualitas tinggi menjadi produk oleh-oleh khas nusantara bernilai tambah.
        </p>
        <p>
          Tanah Fakfak yang subur menghasilkan buah sukun berdaging pulen serta keladi bertekstur garing alami. Dipadukan dengan keripik pisang aneka rasa kontemporer, KRISUKA menyajikan pengalaman ngemil yang renyah, lezat, dan otentik.
        </p>

        <div class="about-highlights-list">
          <div class="about-highlight-box">
            <strong>📍 Asli Produksi Fakfak</strong>
            <span>Diolah langsung di Fakfak, Papua Barat.</span>
          </div>
          <div class="about-highlight-box">
            <strong>🤝 Berdayakan Petani</strong>
            <span>Menyerap panen sukun, keladi, dan pisang lokal.</span>
          </div>
          <div class="about-highlight-box">
            <strong>✨ Renyah &amp; Bersih</strong>
            <span>Standar penggorengan higienis tanpa minyak jenuh.</span>
          </div>
          <div class="about-highlight-box">
            <strong>💬 Pesan Langsung via WA</strong>
            <span>Respon cepat dan pengiriman terpercaya.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($homeReviews)): ?>
<!-- Customer Reviews & Testimonials Section -->
<section style="padding: 70px 0; background: var(--color-cream-warm); border-top: 1px solid var(--color-cream-border); border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <div class="section-header" style="text-align: center; margin-bottom: 45px;">
      <span class="section-tag">Ulasan Pembeli</span>
      <h2 class="section-title">Apa Kata Mereka Tentang KRISUKA?</h2>
      <p class="section-desc">Testimoni asli dan saran rasa dari para penikmat keripik Fakfak di berbagai kota.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
      <?php foreach ($homeReviews as $rev): ?>
      <div style="background: #FFFFFF; border: 1px solid var(--color-cream-border); border-radius: var(--radius-md); padding: 26px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <?= render_star_rating((float)$rev['rating']) ?>
            <span style="font-size: 0.78rem; color: var(--color-text-muted);"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
          </div>
          <p style="color: #374151; font-size: 0.94rem; line-height: 1.7; font-style: italic; margin-bottom: 20px;">
            "<?= e($rev['review_text']) ?>"
          </p>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px dashed var(--color-cream-border); padding-top: 14px; gap: 10px;">
          <div>
            <strong style="display: block; color: var(--color-primary-dark); font-size: 0.95rem;"><?= e($rev['customer_name']) ?></strong>
            <small style="color: var(--color-text-muted);">📍 <?= e($rev['customer_city']) ?></small>
          </div>
          <a href="<?= base_url('product-detail.php?id=' . $rev['product_id']) ?>#reviewsSection" style="font-size: 0.8rem; font-weight: 700; color: var(--color-primary); background: var(--color-cream-bg); padding: 5px 12px; border-radius: var(--radius-pill); text-decoration: none; white-space: nowrap;">
            <?= e($rev['product_name']) ?> &rarr;
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ Accordion Section -->
<section class="section-faq" id="faq">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Tanya Jawab</span>
      <h2 class="section-title">Pertanyaan Seputar Pemesanan</h2>
      <p class="section-desc">Berikut jawaban atas pertanyaan yang sering diajukan seputar produk keripik KRISUKA dan proses pengiriman.</p>
    </div>

    <div class="faq-container">
      <div class="faq-item active">
        <button type="button" class="faq-question-btn">
          <span>Berapa lama daya tahan dan kerenyahan keripik KRISUKA?</span>
          <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <div class="faq-answer-panel" style="max-height: 200px;">
          <div class="faq-answer-content">
            Keripik KRISUKA dapat bertahan 3 hingga 6 bulan dalam kemasan tertutup rapat. Simpanlah di tempat yang sejuk, kering, dan hindari paparan sinar matahari langsung agar kerenyahan tetap terjaga maksimal.
          </div>
        </div>
      </div>

      <div class="faq-item">
        <button type="button" class="faq-question-btn">
          <span>Apakah KRISUKA melayani pengiriman ke luar kota Fakfak atau luar pulau Papua?</span>
          <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <div class="faq-answer-panel">
          <div class="faq-answer-content">
            Ya! Kami melayani pengiriman pesanan ke seluruh Indonesia menggunakan jasa ekspedisi terpercaya. Semua pesanan dikemas dengan kardus tebal dan lapisan bubble wrap rapi untuk menjaga produk tidak remuk saat dalam perjalanan.
          </div>
        </div>
      </div>

      <div class="faq-item">
        <button type="button" class="faq-question-btn">
          <span>Bagaimana cara memesan dan melakukan pembayaran?</span>
          <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <div class="faq-answer-panel">
          <div class="faq-answer-content">
            Cukup pilih produk yang Anda inginkan di website ini, klik "Checkout WhatsApp", lalu isi data alamat Anda. Pesanan akan otomatis terformat ke nomor WhatsApp resmi KRISUKA. Admin kami akan mengonfirmasi total biaya termasuk ongkos kirim dan nomor rekening pembayaran resmi toko.
          </div>
        </div>
      </div>

      <div class="faq-item">
        <button type="button" class="faq-question-btn">
          <span>Apakah ada minimal order pembelian?</span>
          <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>
        <div class="faq-answer-panel">
          <div class="faq-answer-content">
            Tidak ada batas minimal order! Kami siap melayani pemesanan mulai dari 1 bungkus untuk dicoba, maupun pesanan jumlah banyak untuk oleh-oleh atau acara keluarga.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact Section -->
<section class="section-contact" id="kontak">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Hubungi Kami</span>
      <h2 class="section-title">Kontak Resmi KRISUKA</h2>
      <p class="section-desc">Punya pertanyaan seputar ketersediaan stok atau pemesanan partai besar? Hubungi kami langsung.</p>
    </div>

    <div class="contact-grid">
      <div class="contact-card">
        <div class="contact-icon-bubble">💬</div>
        <h3>WhatsApp Resmi</h3>
        <p>Layanan tanya jawab dan konfirmasi pesanan cepat setiap hari.</p>
        <a href="https://wa.me/<?= e(get_setting('whatsapp_number')) ?>" class="contact-action-btn" target="_blank" rel="noopener noreferrer">
          Chat WhatsApp
        </a>
      </div>

      <div class="contact-card">
        <div class="contact-icon-bubble">📸</div>
        <h3>Instagram Brand</h3>
        <p>Ikuti update varian terbaru, promo menarik, dan aktivitas dapur KRISUKA.</p>
        <a href="<?= e(get_setting('instagram_url')) ?>" class="contact-action-btn" target="_blank" rel="noopener noreferrer">
          Kunjungi Instagram
        </a>
      </div>

      <div class="contact-card">
        <div class="contact-icon-bubble">📍</div>
        <h3>Pusat Produksi</h3>
        <p><?= e(get_setting('store_address')) ?></p>
        <a href="<?= base_url('products.php') ?>" class="contact-action-btn">
          Pesan Sekarang
        </a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


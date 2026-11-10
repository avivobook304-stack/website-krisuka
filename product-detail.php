<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'products';
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: ' . base_url('products.php'));
    exit;
}

$stmt = db()->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan atau sudah tidak tersedia.');
    header('Location: ' . base_url('products.php'));
    exit;
}

// Handle Form Kirim Saran & Rating Produk
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    if (!verify_csrf()) {
        set_flash('danger', 'Token keamanan kedaluwarsa. Silakan muat ulang halaman.');
        header('Location: ' . base_url('product-detail.php?id=' . $productId . '#reviewsSection'));
        exit;
    }

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerCity = trim($_POST['customer_city'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $reviewText = trim($_POST['review_text'] ?? '');

    if (empty($customerCity)) {
        $customerCity = 'Fakfak';
    }

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    if (mb_strlen($customerName) < 2) {
        set_flash('danger', 'Nama Anda wajib diisi (minimal 2 karakter).');
    } elseif (mb_strlen($reviewText) < 5) {
        set_flash('danger', 'Mohon tuliskan ulasan atau saran Anda (minimal 5 karakter).');
    } else {
        try {
            $insertReview = db()->prepare("INSERT INTO product_reviews 
                (product_id, customer_name, customer_city, rating, review_text, is_approved, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, ?)");
            $insertReview->execute([
                $productId,
                $customerName,
                $customerCity,
                $rating,
                $reviewText,
                date('Y-m-d H:i:s')
            ]);
            set_flash('success', '✨ Terima kasih banyak! Ulasan dan saran rasa Anda berhasil disimpan.');
        } catch (Exception $e) {
            set_flash('danger', 'Terjadi kesalahan saat menyimpan ulasan: ' . $e->getMessage());
        }
    }

    header('Location: ' . base_url('product-detail.php?id=' . $productId . '#reviewsSection'));
    exit;
}

// Ambil statistik rating produk
$ratingStats = get_product_rating_stats($productId);

// Ambil daftar ulasan produk yang disetujui
$stmtReviews = db()->prepare("SELECT * FROM product_reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$stmtReviews->execute([$productId]);
$reviews = $stmtReviews->fetchAll();

$pageTitle = e($product['name']) . ' | KRISUKA Fakfak';
$pageDesc = e(mb_strimwidth($product['description'], 0, 160, '...'));
$ogImage = product_image_url($product['image']);

// Related products
$relatedStmt = db()->prepare("SELECT * FROM products WHERE id != ? AND is_active = 1 ORDER BY (category_id = ?) DESC, id ASC LIMIT 3");
$relatedStmt->execute([$productId, $product['category_id']]);
$relatedProducts = $relatedStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 20px 0; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <nav style="font-size: 0.88rem; color: var(--color-text-muted);">
      <a href="<?= base_url('index.php') ?>">Beranda</a> &bull; 
      <a href="<?= base_url('products.php') ?>">Katalog Produk</a> &bull; 
      <span style="color: var(--color-primary-dark); font-weight: 700;"><?= e($product['name']) ?></span>
    </nav>
  </div>
</div>

<section style="padding: 50px 0 80px;">
  <div class="container">
    <?= flash_message() ?>

    <div class="product-detail-grid">
      <!-- Product Image Column -->
      <div>
        <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; background: #FFFFFF; border: 1px solid var(--color-cream-border); box-shadow: var(--shadow-md);">
          <span class="product-badge-flavor" style="top: 20px; left: 20px; font-size: 0.85rem;">
            <?= e($product['badge']) ?>
          </span>
          <img 
            src="<?= e(product_image_url($product['image'])) ?>" 
            alt="<?= e($product['name']) ?>" 
            style="width: 100%; height: 440px; object-fit: cover;"
            onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'600\' height=\'400\' viewBox=\'0 0 600 400\'><rect width=\'100%\' height=\'100%\' fill=\'%231B4D3E\'/><text x=\'50%\' y=\'50%\' fill=\'%23FAF7F0\' font-size=\'24\' font-weight=\'bold\' text-anchor=\'middle\'><?= urlencode($product['name']) ?></text></svg>';"
          >
        </div>
      </div>

      <!-- Product Details Column -->
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
          <span style="display: inline-block; background: var(--color-primary-soft); color: var(--color-primary); font-size: 0.8rem; font-weight: 700; padding: 4px 12px; border-radius: var(--radius-pill); text-transform: uppercase;">
            <?= e($product['category_name']) ?>
          </span>
          <span style="display: inline-block; background: #FEF3C7; color: #92400E; font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-pill);">
            🌾 Khas Asli Fakfak
          </span>
        </div>

        <h1 style="font-family: var(--font-heading); font-size: 2.3rem; color: var(--color-primary-dark); line-height: 1.2; margin-bottom: 12px;">
          <?= e($product['name']) ?>
        </h1>

        <!-- Rating & Review Summary Line -->
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 4px;">
            <?= $ratingStats['stars_html'] ?>
            <strong style="color: var(--color-primary-dark); font-size: 1rem; margin-left: 4px;"><?= number_format($ratingStats['rating'], 1) ?></strong>
          </div>
          <span style="color: var(--color-text-muted); font-size: 0.9rem;">
            (<?= $ratingStats['count'] ?> ulasan &amp; saran)
          </span>
          <span style="color: var(--color-cream-border);">&bull;</span>
          <a href="#reviewsSection" style="color: var(--color-primary); font-size: 0.88rem; font-weight: 600; text-decoration: underline;">
            Lihat Ulasan / Beri Masukan &rarr;
          </a>
        </div>

        <div style="display: flex; align-items: baseline; gap: 16px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px dashed var(--color-cream-border);">
          <span style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">
            <?= format_rupiah($product['price']) ?>
          </span>
          <span style="color: var(--color-text-muted); font-size: 0.95rem;">
            / kemasan (<?= e($product['netto']) ?>)
          </span>
        </div>

        <div style="margin-bottom: 24px;">
          <h4 style="font-size: 1rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px;">Deskripsi Produk:</h4>
          <p style="color: var(--color-text-muted); line-height: 1.8; font-size: 0.95rem;">
            <?= nl2br(e($product['description'])) ?>
          </p>
        </div>

        <!-- Specifications Table -->
        <div style="background: var(--color-cream-bg); border: 1px solid var(--color-cream-border); border-radius: var(--radius-md); padding: 18px 20px; margin-bottom: 28px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.88rem;">
            <div>
              <span style="color: var(--color-text-muted); display: block;">Asal Daerah:</span>
              <strong style="color: var(--color-primary-dark);">Fakfak, Papua Barat</strong>
            </div>
            <div>
              <span style="color: var(--color-text-muted); display: block;">Berat Bersih:</span>
              <strong style="color: var(--color-primary-dark);"><?= e($product['netto']) ?></strong>
            </div>
            <div>
              <span style="color: var(--color-text-muted); display: block;">Ketersediaan Stok:</span>
              <strong style="color: <?= ($product['stock'] > 10) ? 'var(--color-success)' : 'var(--color-warning)' ?>;">
                <?= (int)$product['stock'] ?> kemasan siap kirim
              </strong>
            </div>
            <div>
              <span style="color: var(--color-text-muted); display: block;">Ketahanan Produk:</span>
              <strong style="color: var(--color-primary-dark);">3 - 6 Bulan (Kedap Udara)</strong>
            </div>
          </div>
        </div>

        <!-- Purchase Action Form -->
        <form action="<?= base_url('cart-action.php') ?>" method="POST" style="margin-bottom: 30px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

          <div class="product-buy-row" style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px;">
            <!-- Stepper -->
            <div style="display: flex; align-items: center; background: #FFFFFF; border: 1px solid var(--color-cream-border); border-radius: var(--radius-pill); padding: 6px 12px; gap: 8px;">
              <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-text-muted); margin-right: 4px;">Jumlah:</span>
              <button type="button" class="qty-btn" onclick="stepCardQty(<?= $product['id'] ?>, -1)">-</button>
              <input type="text" id="qty-input-<?= $product['id'] ?>" name="quantity" class="qty-input" value="1" readonly style="font-size: 1rem; width: 40px;">
              <button type="button" class="qty-btn" onclick="stepCardQty(<?= $product['id'] ?>, 1)">+</button>
            </div>

            <!-- Buttons -->
            <button type="submit" class="btn btn-primary btn-product-cart" style="padding: 14px 28px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
              </svg>
              Tambah ke Keranjang
            </button>

            <button type="submit" name="buy_now" value="1" class="btn btn-gold btn-product-buy" style="padding: 14px 28px;">
              Beli Sekarang
            </button>
          </div>
        </form>

        <!-- Share Product to WhatsApp / Social Media -->
        <?php 
        $shareText = "Cobain kelezatan " . $product['name'] . " (" . $product['badge'] . ") khas Fakfak Papua Barat! Renyah dan lezat: " . base_url('product-detail.php?id=' . $product['id']);
        $shareWaUrl = "https://api.whatsapp.com/send?text=" . urlencode($shareText);
        ?>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 0.88rem; color: var(--color-text-muted); padding-top: 8px; border-top: 1px dashed var(--color-cream-border); flex-wrap: wrap;">
          <span style="font-weight: 600;">Bagikan Produk:</span>
          <a href="<?= e($shareWaUrl) ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #25D366; color: #FFFFFF; padding: 6px 14px; border-radius: var(--radius-pill); font-size: 0.82rem; font-weight: 700; text-decoration: none;">
            <span>💬 Bagikan via WhatsApp</span>
          </a>
          <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Tautan produk berhasil disalin ke papan klip!');" style="background: var(--color-cream-bg); border: 1px solid var(--color-cream-border); padding: 6px 12px; border-radius: var(--radius-pill); font-size: 0.82rem; font-weight: 600; cursor: pointer; color: var(--color-primary-dark);">
            🔗 Salin Tautan
          </button>
        </div>
      </div>
    </div>

    <!-- REVIEWS & SUGGESTIONS SECTION -->
    <div id="reviewsSection" style="border-top: 1px solid var(--color-cream-border); padding-top: 50px; margin-bottom: 70px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
          <span style="font-size: 0.82rem; font-weight: 800; color: var(--color-gold-hover); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 4px;">
            Feedback &amp; Kepuasan Pembeli
          </span>
          <h2 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-primary-dark); margin: 0;">
            Ulasan &amp; Saran Rasa Produk
          </h2>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; background: #FFFFFF; padding: 10px 18px; border-radius: var(--radius-pill); border: 1px solid var(--color-cream-border); box-shadow: var(--shadow-sm);">
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-primary); line-height: 1;">
            <?= number_format($ratingStats['rating'], 1) ?>
          </div>
          <div>
            <div style="line-height: 1;"><?= $ratingStats['stars_html'] ?></div>
            <small style="color: var(--color-text-muted); font-size: 0.78rem;">Berdasarkan <?= $ratingStats['count'] ?> ulasan pembeli</small>
          </div>
        </div>
      </div>

      <div class="reviews-grid">
        <!-- Left Column: Reviews List -->
        <div>
          <?php if (empty($reviews)): ?>
            <div style="background: #FFFFFF; border: 1px dashed var(--color-cream-border); border-radius: var(--radius-lg); padding: 40px 24px; text-align: center;">
              <span style="font-size: 2.6rem; display: block; margin-bottom: 12px;">🌟</span>
              <h4 style="font-family: var(--font-heading); color: var(--color-primary-dark); margin-bottom: 8px;">Belum Ada Ulasan untuk Produk Ini</h4>
              <p style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 0;">
                Jadilah pembeli pertama yang memberikan ulasan atau saran rasa keripik ini melalui formulir di samping!
              </p>
            </div>
          <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 18px;">
              <?php foreach ($reviews as $rev): ?>
              <div style="background: #FFFFFF; border: 1px solid var(--color-cream-border); border-radius: var(--radius-md); padding: 22px; box-shadow: var(--shadow-sm); transition: transform 0.2s ease;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                  <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Avatar Initials -->
                    <?php 
                    $initial = strtoupper(mb_substr($rev['customer_name'], 0, 1));
                    $bgColors = ['#1B4D3E', '#2D6A4F', '#0D9488', '#B45309', '#4338CA'];
                    $colorIndex = abs(crc32($rev['customer_name'])) % count($bgColors);
                    $avatarBg = $bgColors[$colorIndex];
                    ?>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: <?= $avatarBg ?>; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem;">
                      <?= e($initial) ?>
                    </div>
                    <div>
                      <div style="display: flex; align-items: center; gap: 6px;">
                        <strong style="color: var(--color-primary-dark); font-size: 0.98rem;"><?= e($rev['customer_name']) ?></strong>
                        <span title="Pembeli Terverifikasi" style="display: inline-flex; align-items: center; background: #DCFCE7; color: #15803D; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: var(--radius-pill);">
                          ✓ Terverifikasi
                        </span>
                      </div>
                      <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px;">
                        <span>📍 <?= e($rev['customer_city']) ?></span> &bull; 
                        <span><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
                      </div>
                    </div>
                  </div>

                  <!-- Stars -->
                  <div>
                    <?= render_star_rating((float)$rev['rating']) ?>
                  </div>
                </div>

                <!-- Review Content -->
                <p style="color: #374151; font-size: 0.94rem; line-height: 1.7; margin: 0; background: var(--color-cream-bg); padding: 14px 16px; border-radius: var(--radius-sm); border-left: 3px solid var(--color-gold);">
                  "<?= nl2br(e($rev['review_text'])) ?>"
                </p>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right Column: Review Submission Form -->
        <div style="background: #FFFFFF; border: 1px solid var(--color-cream-border); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-md); position: sticky; top: 100px;">
          <h3 style="font-family: var(--font-heading); font-size: 1.35rem; color: var(--color-primary-dark); margin-bottom: 6px;">
            Beri Rating &amp; Saran Rasa
          </h3>
          <p style="color: var(--color-text-muted); font-size: 0.88rem; margin-bottom: 22px; line-height: 1.6;">
            Masukan Anda sangat membantu KRISUKA menghadirkan cita rasa keripik Fakfak yang semakin istimewa.
          </p>

          <form action="<?= base_url('product-detail.php?id=' . $productId) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="submit_review">

            <!-- Interactive Star Rating Picker -->
            <div style="margin-bottom: 20px;">
              <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 8px;">
                Penilaian Bintang: <span id="ratingLabel" style="color: #D97706; font-weight: 800;">5 Bintang (Sangat Enak &amp; Renyah)</span>
              </label>
              <input type="hidden" name="rating" id="ratingInput" value="5">

              <div id="starPicker" style="display: flex; gap: 8px; font-size: 1.9rem; cursor: pointer; color: #F59E0B; user-select: none;">
                <span class="picker-star" data-val="1">★</span>
                <span class="picker-star" data-val="2">★</span>
                <span class="picker-star" data-val="3">★</span>
                <span class="picker-star" data-val="4">★</span>
                <span class="picker-star" data-val="5">★</span>
              </div>
            </div>

            <div style="margin-bottom: 16px;">
              <label for="rev_name" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">
                Nama Lengkap / Panggilan <span style="color: var(--color-accent);">*</span>
              </label>
              <input 
                type="text" 
                id="rev_name" 
                name="customer_name" 
                required 
                maxlength="100" 
                placeholder="Contoh: Rahmat / Kak Maria" 
                class="form-control" 
                style="width: 100%; padding: 12px 14px; border: 1px solid var(--color-cream-border); border-radius: var(--radius-sm); font-size: 0.92rem;"
              >
            </div>

            <div style="margin-bottom: 16px;">
              <label for="rev_city" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">
                Kota / Domisili
              </label>
              <input 
                type="text" 
                id="rev_city" 
                name="customer_city" 
                maxlength="100" 
                placeholder="Contoh: Fakfak, Sorong, Jayapura, Jakarta..." 
                class="form-control" 
                style="width: 100%; padding: 12px 14px; border: 1px solid var(--color-cream-border); border-radius: var(--radius-sm); font-size: 0.92rem;"
              >
            </div>

            <div style="margin-bottom: 22px;">
              <label for="rev_text" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">
                Ulasan &amp; Saran Rasa / Kemasan <span style="color: var(--color-accent);">*</span>
              </label>
              <textarea 
                id="rev_text" 
                name="review_text" 
                required 
                rows="4" 
                maxlength="1000" 
                placeholder="Ceritakan kerenyahan rasa keripik ini dan saran varian bumbu atau ukuran kemasan yang Anda harapkan..." 
                class="form-control" 
                style="width: 100%; padding: 12px 14px; border: 1px solid var(--color-cream-border); border-radius: var(--radius-sm); font-size: 0.92rem; resize: vertical;"
              ></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; justify-content: center; font-weight: 700; font-size: 0.95rem;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              Kirim Ulasan &amp; Saran Rasa
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <div style="border-top: 1px solid var(--color-cream-border); padding-top: 60px;">
      <h3 style="font-family: var(--font-heading); font-size: 1.8rem; color: var(--color-primary-dark); margin-bottom: 24px;">
        Varian Keripik Lainnya yang Mungkin Anda Suka
      </h3>
      <div class="products-grid">
        <?php foreach ($relatedProducts as $rel): ?>
        <?php $relRating = get_product_rating_stats((int)$rel['id']); ?>
        <article class="product-card">
          <a href="<?= base_url('product-detail.php?id=' . $rel['id']) ?>" class="product-thumb-container">
            <span class="product-badge-flavor"><?= e($rel['badge']) ?></span>
            <img src="<?= e(product_image_url($rel['image'])) ?>" alt="<?= e($rel['name']) ?>" class="product-thumb-img" loading="lazy">
            <span class="product-netto-tag">⚖️ <?= e($rel['netto']) ?></span>
          </a>
          <div class="product-details">
            <h3 class="product-title">
              <a href="<?= base_url('product-detail.php?id=' . $rel['id']) ?>"><?= e($rel['name']) ?></a>
            </h3>

            <!-- Rating badge on card -->
            <div style="display: flex; align-items: center; gap: 6px; margin: 4px 0 10px; font-size: 0.82rem;">
              <?= $relRating['stars_html'] ?>
              <span style="font-weight: 700; color: var(--color-primary-dark);"><?= number_format($relRating['rating'], 1) ?></span>
              <span style="color: var(--color-text-muted); font-size: 0.78rem;">(<?= $relRating['count'] ?>)</span>
            </div>

            <div class="product-pricing-row">
              <span class="product-price-label">Harga Satuan:</span>
              <span class="product-price-value"><?= format_rupiah($rel['price']) ?></span>
            </div>
            <a href="<?= base_url('product-detail.php?id=' . $rel['id']) ?>" class="btn btn-secondary" style="width: 100%;">
              Lihat Detail
            </a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
// Interactive Star Picker Logic
(function() {
  const ratingInput = document.getElementById('ratingInput');
  const ratingLabel = document.getElementById('ratingLabel');
  const starPicker = document.getElementById('starPicker');
  if (!starPicker || !ratingInput) return;

  const stars = starPicker.querySelectorAll('.picker-star');
  const ratingTexts = {
    1: '1 Bintang (Perlu Peningkatan)',
    2: '2 Bintang (Cukup Biasa)',
    3: '3 Bintang (Lumayan Enak)',
    4: '4 Bintang (Enak & Renyah)',
    5: '5 Bintang (Sangat Enak & Renyah)'
  };

  function setStars(val) {
    ratingInput.value = val;
    ratingLabel.textContent = ratingTexts[val] || (val + ' Bintang');
    stars.forEach(star => {
      const starVal = parseInt(star.getAttribute('data-val'), 10);
      if (starVal <= val) {
        star.style.color = '#F59E0B';
        star.textContent = '★';
      } else {
        star.style.color = '#D1D5DB';
        star.textContent = '☆';
      }
    });
  }

  stars.forEach(star => {
    star.addEventListener('click', function() {
      const val = parseInt(this.getAttribute('data-val'), 10);
      setStars(val);
    });

    star.addEventListener('mouseenter', function() {
      const hoverVal = parseInt(this.getAttribute('data-val'), 10);
      stars.forEach(s => {
        const sVal = parseInt(s.getAttribute('data-val'), 10);
        s.textContent = (sVal <= hoverVal) ? '★' : '☆';
        s.style.color = (sVal <= hoverVal) ? '#F59E0B' : '#D1D5DB';
      });
    });
  });

  starPicker.addEventListener('mouseleave', function() {
    setStars(parseInt(ratingInput.value, 10) || 5);
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

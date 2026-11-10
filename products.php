<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'products';
$pageTitle = 'Katalog Produk KRISUKA | Keripik Sukun, Keladi & Pisang Fakfak';

// Filter & Search Params
$search = sanitize($_GET['q'] ?? '');
$categorySlug = sanitize($_GET['category'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');

// Fetch all categories for filter pills
$categories = db()->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Build Query
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.badge LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($categorySlug) && $categorySlug !== 'all') {
    $sql .= " AND c.slug = ?";
    $params[] = $categorySlug;
}

// Sorting logic
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 40px 0 20px; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <span class="section-tag">Koleksi Lengkap</span>
    <h1 class="section-title" style="margin-bottom: 8px;">Katalog Keripik Lokal KRISUKA</h1>
    <p class="section-desc">Pilihan oleh-oleh khas Fakfak Papua Barat: Keripik Sukun, Keripik Keladi, dan Keripik Pisang aneka rasa istimewa.</p>
  </div>
</div>

<section class="section-products" style="padding-top: 40px;">
  <div class="container">
    <!-- Controls Bar -->
    <div class="catalog-controls-card">
      <!-- Search Input -->
      <form action="<?= base_url('products.php') ?>" method="GET" class="search-input-wrapper">
        <?php if (!empty($categorySlug)): ?>
          <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
        <?php endif; ?>
        <?php if (!empty($sort)): ?>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <?php endif; ?>

        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input 
          type="text" 
          name="q" 
          class="search-input" 
          placeholder="Cari varian keripik (Sukun, Keladi, Balado, Coklat)..." 
          value="<?= e($search) ?>"
        >
      </form>

      <!-- Category Filter Pills -->
      <div class="filter-pills-group">
        <a href="<?= base_url('products.php?sort=' . urlencode($sort) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" 
           class="filter-pill <?= empty($categorySlug) || $categorySlug === 'all' ? 'active' : '' ?>">
          Semua Produk
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= base_url('products.php?category=' . urlencode($cat['slug']) . '&sort=' . urlencode($sort) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" 
           class="filter-pill <?= ($categorySlug === $cat['slug']) ? 'active' : '' ?>">
          <?= e($cat['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Sorting Selector -->
      <div class="sort-select-wrapper">
        <label for="sortSelect" style="font-size: 0.85rem; font-weight: 700; color: var(--color-primary-dark);">Urutkan:</label>
        <select id="sortSelect" class="sort-select" onchange="location = this.value;">
          <option value="<?= base_url('products.php?sort=newest' . (!empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '') . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" <?= ($sort === 'newest') ? 'selected' : '' ?>>Terbaru</option>
          <option value="<?= base_url('products.php?sort=price_low' . (!empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '') . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" <?= ($sort === 'price_low') ? 'selected' : '' ?>>Harga Termurah</option>
          <option value="<?= base_url('products.php?sort=price_high' . (!empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '') . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" <?= ($sort === 'price_high') ? 'selected' : '' ?>>Harga Termahal</option>
          <option value="<?= base_url('products.php?sort=name' . (!empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '') . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" <?= ($sort === 'name') ? 'selected' : '' ?>>Nama (A-Z)</option>
        </select>
      </div>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px dashed var(--color-cream-border); padding: 60px 20px; text-align: center;">
        <span style="font-size: 3rem; display: block; margin-bottom: 12px;">🔍</span>
        <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-primary-dark); margin-bottom: 8px;">
          Produk Tidak Ditemukan
        </h3>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 24px;">
          Tidak ada produk yang cocok dengan pencarian "<strong><?= e($search) ?></strong>".
        </p>
        <a href="<?= base_url('products.php') ?>" class="btn btn-secondary">
          Tampilkan Semua Produk
        </a>
      </div>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $prod): ?>
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
            <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-gold-hover); font-weight: 700; margin-bottom: 4px;">
              <?= e($prod['category_name']) ?>
            </div>
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
              <span class="product-price-label">Harga Satuan:</span>
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
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


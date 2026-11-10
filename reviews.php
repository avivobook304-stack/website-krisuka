<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Manajemen Ulasan & Saran Produk';
$adminNav = 'reviews';

$errors = [];

// Handle Tambah Ulasan Manual oleh Admin
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi token keamanan gagal.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);
        $customerName = sanitize($_POST['customer_name'] ?? '');
        $customerCity = sanitize($_POST['customer_city'] ?? 'Fakfak');
        $rating = (int)($_POST['rating'] ?? 5);
        $reviewText = trim($_POST['review_text'] ?? '');
        $isApproved = (int)($_POST['is_approved'] ?? 1);

        if ($productId <= 0) {
            $errors[] = 'Silakan pilih varian produk yang diulas.';
        } elseif (empty($customerName)) {
            $errors[] = 'Nama pelanggan wajib diisi.';
        } elseif (empty($reviewText)) {
            $errors[] = 'Ulasan atau saran produk wajib diisi.';
        } else {
            if ($rating < 1 || $rating > 5) $rating = 5;
            $stmt = db()->prepare("INSERT INTO product_reviews 
                (product_id, customer_name, customer_city, rating, review_text, is_approved, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $productId,
                $customerName,
                !empty($customerCity) ? $customerCity : 'Fakfak',
                $rating,
                $reviewText,
                $isApproved,
                date('Y-m-d H:i:s')
            ]);
            set_flash('success', "Ulasan dari '$customerName' berhasil ditambahkan!");
            header('Location: ' . base_url('admin/reviews.php'));
            exit;
        }
    }
}

// Handle Toggle Status Publikasi (Tampil / Sembunyi)
if (isset($_GET['toggle_id'])) {
    if (verify_csrf()) {
        $toggleId = (int)$_GET['toggle_id'];
        db()->prepare("UPDATE product_reviews SET is_approved = (1 - is_approved) WHERE id = ?")->execute([$toggleId]);
        set_flash('info', 'Status moderasi ulasan berhasil diperbarui.');
    }
    header('Location: ' . base_url('admin/reviews.php'));
    exit;
}

// Handle Hapus Ulasan
if (isset($_GET['delete_id'])) {
    if (verify_csrf()) {
        $delId = (int)$_GET['delete_id'];
        db()->prepare("DELETE FROM product_reviews WHERE id = ?")->execute([$delId]);
        set_flash('success', 'Ulasan pembeli berhasil dihapus.');
    }
    header('Location: ' . base_url('admin/reviews.php'));
    exit;
}

// Filter & Pencarian
$statusFilter = sanitize($_GET['status'] ?? 'all');
$productFilter = (int)($_GET['product_id'] ?? 0);
$ratingFilter = (int)($_GET['rating'] ?? 0);
$search = sanitize($_GET['q'] ?? '');

$sql = "SELECT r.*, p.name as product_name, p.badge as product_badge, p.image as product_image 
        FROM product_reviews r 
        LEFT JOIN products p ON r.product_id = p.id 
        WHERE 1=1";
$params = [];

if ($statusFilter === 'approved') {
    $sql .= " AND r.is_approved = 1";
} elseif ($statusFilter === 'pending') {
    $sql .= " AND r.is_approved = 0";
}

if ($productFilter > 0) {
    $sql .= " AND r.product_id = ?";
    $params[] = $productFilter;
}

if ($ratingFilter >= 1 && $ratingFilter <= 5) {
    $sql .= " AND r.rating = ?";
    $params[] = $ratingFilter;
}

if (!empty($search)) {
    $sql .= " AND (r.customer_name LIKE ? OR r.customer_city LIKE ? OR r.review_text LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Hitung Statistik Ulasan
$totalReviews = (int)db()->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
$avgRating = (float)db()->query("SELECT COALESCE(AVG(rating), 5.0) FROM product_reviews WHERE is_approved = 1")->fetchColumn();
$approvedCount = (int)db()->query("SELECT COUNT(*) FROM product_reviews WHERE is_approved = 1")->fetchColumn();
$pendingCount = (int)db()->query("SELECT COUNT(*) FROM product_reviews WHERE is_approved = 0")->fetchColumn();

// Ambil semua produk untuk pilihan filter & form input
$allProducts = db()->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($errors)): ?>
<div style="background: #FEE2E2; border: 1px solid #F87171; color: #991B1B; padding: 14px 18px; border-radius: var(--admin-radius); margin-bottom: 24px; font-size: 0.92rem;">
  <strong>Perhatian:</strong>
  <ul style="margin: 6px 0 0 18px; padding: 0;">
    <?php foreach ($errors as $err): ?>
      <li><?= e($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- Metric Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon-wrapper green">⭐</div>
    <div class="stat-data">
      <small>Rata-rata Rating Toko</small>
      <h3 style="color: #D97706;"><?= number_format($avgRating, 1) ?> <span style="font-size: 0.95rem; font-weight: normal; color: var(--admin-text-muted);">/ 5.0</span></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon-wrapper blue">💬</div>
    <div class="stat-data">
      <small>Total Ulasan &amp; Saran</small>
      <h3><?= $totalReviews ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon-wrapper gold">✅</div>
    <div class="stat-data">
      <small>Ulasan Tampil (Aktif)</small>
      <h3 style="color: #15803D;"><?= $approvedCount ?></h3>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon-wrapper orange">⏳</div>
    <div class="stat-data">
      <small>Ulasan Disembunyikan</small>
      <h3 style="color: <?= $pendingCount > 0 ? '#B45309' : 'var(--admin-text-muted)' ?>;"><?= $pendingCount ?></h3>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Daftar Feedback, Ulasan &amp; Saran Rasa</h3>
      <small style="color: var(--admin-text-muted);">Moderasi ulasan pembeli dan masukkan saran rasa baru yang dikirimkan via WhatsApp.</small>
    </div>
    <button type="button" class="btn-action btn-action-primary" onclick="toggleAddForm()">
      + Tambah Ulasan Manual
    </button>
  </div>

  <!-- Collapsible Add Review Form -->
  <div id="addReviewForm" style="display: none; background: #F8FAFC; border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 24px; margin-bottom: 26px;">
    <h4 style="font-size: 1.1rem; color: var(--admin-primary); margin-bottom: 14px;">Tambah Ulasan / Saran Pelanggan Manual</h4>
    <form action="<?= base_url('admin/reviews.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_review">

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
        <div>
          <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">Pilih Produk KRISUKA *</label>
          <select name="product_id" required class="form-control" style="width: 100%;">
            <option value="">-- Pilih Varian Produk --</option>
            <?php foreach ($allProducts as $p): ?>
              <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">Nama Pelanggan *</label>
          <input type="text" name="customer_name" required placeholder="Contoh: Rahmat / Ibu Siti" class="form-control" style="width: 100%;">
        </div>

        <div>
          <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">Kota Asal / Domisili</label>
          <input type="text" name="customer_city" placeholder="Contoh: Fakfak / Manokwari" class="form-control" style="width: 100%;">
        </div>

        <div>
          <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">Nilai Bintang</label>
          <select name="rating" class="form-control" style="width: 100%;">
            <option value="5" selected>⭐⭐⭐⭐⭐ (5 Bintang)</option>
            <option value="4">⭐⭐⭐⭐ (4 Bintang)</option>
            <option value="3">⭐⭐⭐ (3 Bintang)</option>
            <option value="2">⭐⭐ (2 Bintang)</option>
            <option value="1">⭐ (1 Bintang)</option>
          </select>
        </div>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">Isi Ulasan &amp; Saran Rasa / Kemasan *</label>
        <textarea name="review_text" required rows="3" placeholder="Tuliskan testimoni atau saran pelanggan..." class="form-control" style="width: 100%;"></textarea>
      </div>

      <div style="display: flex; gap: 12px; align-items: center;">
        <button type="submit" class="btn-action btn-action-primary">Simpan Ulasan</button>
        <button type="button" class="btn-action btn-action-edit" onclick="toggleAddForm()">Batal</button>
      </div>
    </form>
  </div>

  <!-- Search & Filter Controls -->
  <form action="<?= base_url('admin/reviews.php') ?>" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;">
    <input 
      type="text" 
      name="q" 
      class="form-control" 
      placeholder="Cari nama, kota, atau isi saran..." 
      value="<?= e($search) ?>" 
      style="min-width: 240px; flex: 1;"
    >

    <select name="product_id" class="form-control" style="min-width: 200px;">
      <option value="0">-- Semua Varian Produk --</option>
      <?php foreach ($allProducts as $p): ?>
        <option value="<?= $p['id'] ?>" <?= ($productFilter === (int)$p['id']) ? 'selected' : '' ?>>
          <?= e($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" class="form-control" style="min-width: 160px;">
      <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>Semua Status</option>
      <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : '' ?>>Disetujui (Tampil)</option>
      <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Disembunyikan</option>
    </select>

    <select name="rating" class="form-control" style="min-width: 140px;">
      <option value="0" <?= ($ratingFilter === 0) ? 'selected' : '' ?>>Semua Bintang</option>
      <option value="5" <?= ($ratingFilter === 5) ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5)</option>
      <option value="4" <?= ($ratingFilter === 4) ? 'selected' : '' ?>>⭐⭐⭐⭐ (4)</option>
      <option value="3" <?= ($ratingFilter === 3) ? 'selected' : '' ?>>⭐⭐⭐ (3)</option>
      <option value="2" <?= ($ratingFilter === 2) ? 'selected' : '' ?>>⭐⭐ (2)</option>
      <option value="1" <?= ($ratingFilter === 1) ? 'selected' : '' ?>>⭐ (1)</option>
    </select>

    <button type="submit" class="btn-action btn-action-primary">Filter</button>

    <?php if (!empty($search) || $productFilter > 0 || $statusFilter !== 'all' || $ratingFilter > 0): ?>
      <a href="<?= base_url('admin/reviews.php') ?>" class="btn-action btn-action-edit">Reset</a>
    <?php endif; ?>
  </form>

  <!-- Table Reviews -->
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>Pelanggan</th>
          <th>Produk</th>
          <th>Rating</th>
          <th>Ulasan &amp; Saran Rasa</th>
          <th>Status</th>
          <th style="width: 180px; text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reviews)): ?>
        <tr>
          <td colspan="7" style="text-align: center; padding: 40px; color: var(--admin-text-muted);">
            Tidak ada data ulasan yang sesuai dengan kriteria filter.
          </td>
        </tr>
        <?php else: ?>
          <?php foreach ($reviews as $idx => $r): ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td>
              <strong style="color: var(--admin-primary); display: block; font-size: 0.95rem;"><?= e($r['customer_name']) ?></strong>
              <small style="color: var(--admin-text-muted);">📍 <?= e($r['customer_city']) ?></small>
              <div style="font-size: 0.75rem; color: #94A3B8; margin-top: 2px;">
                <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
              </div>
            </td>
            <td>
              <a href="<?= base_url('product-detail.php?id=' . $r['product_id']) ?>" target="_blank" style="font-weight: 700; color: var(--admin-primary); text-decoration: underline;">
                <?= e($r['product_name'] ?? 'Produk #' . $r['product_id']) ?>
              </a>
              <?php if (!empty($r['product_badge'])): ?>
                <span style="display: block; font-size: 0.75rem; color: var(--admin-text-muted);">Varian: <?= e($r['product_badge']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div style="line-height: 1.2;">
                <?= render_star_rating((float)$r['rating']) ?>
                <span style="display: block; font-size: 0.82rem; font-weight: 700; color: #D97706; margin-top: 2px;">
                  <?= (int)$r['rating'] ?> / 5
                </span>
              </div>
            </td>
            <td>
              <div style="background: #F8FAFC; border-left: 3px solid #D97706; padding: 10px 14px; border-radius: 4px; font-size: 0.9rem; color: #334155; max-width: 380px; line-height: 1.5;">
                "<?= nl2br(e($r['review_text'])) ?>"
              </div>
            </td>
            <td>
              <?php if ((int)$r['is_approved'] === 1): ?>
                <span class="badge" style="background: #DCFCE7; color: #15803D; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem;">
                  ✓ Tampil di Web
                </span>
              <?php else: ?>
                <span class="badge" style="background: #FEF3C7; color: #B45309; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem;">
                  ⏸ Disembunyikan
                </span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <div style="display: flex; gap: 6px; justify-content: center;">
                <!-- Toggle Status Button -->
                <a 
                  href="<?= base_url('admin/reviews.php?toggle_id=' . $r['id'] . '&csrf_token=' . csrf_token()) ?>" 
                  class="btn-action <?= (int)$r['is_approved'] === 1 ? 'btn-action-edit' : 'btn-action-primary' ?>"
                  title="<?= (int)$r['is_approved'] === 1 ? 'Sembunyikan dari Web' : 'Publikasikan ke Web' ?>"
                  style="font-size: 0.8rem; padding: 6px 10px;"
                >
                  <?= (int)$r['is_approved'] === 1 ? 'Sembunyikan' : 'Tampilkan' ?>
                </a>

                <!-- Delete Button -->
                <a 
                  href="<?= base_url('admin/reviews.php?delete_id=' . $r['id'] . '&csrf_token=' . csrf_token()) ?>" 
                  class="btn-action" 
                  style="background: #FEE2E2; color: #DC2626; font-size: 0.8rem; padding: 6px 10px;"
                  onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini secara permanen?');"
                  title="Hapus Ulasan"
                >
                  Hapus
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleAddForm() {
  const f = document.getElementById('addReviewForm');
  if (f) {
    f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

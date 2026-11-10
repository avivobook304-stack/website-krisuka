<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Tambah Produk Baru';
$adminNav = 'products';

$errors = [];
$categories = db()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi keamanan gagal. Silakan coba lagi.';
    }

    $name = sanitize($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $badge = sanitize($_POST['badge'] ?? 'Renyah Gurih');
    $price = (float)($_POST['price'] ?? 10000);
    $stock = (int)($_POST['stock'] ?? 50);
    $netto = sanitize($_POST['netto'] ?? '100 gram');
    $description = sanitize($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($name)) {
        $errors[] = 'Nama produk wajib diisi.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Silakan pilih kategori produk.';
    }
    if ($price <= 0) {
        $errors[] = 'Harga produk harus lebih dari 0.';
    }

    // Handle Upload Gambar
    $imagePath = 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80';
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploaded = upload_product_image($_FILES['image']);
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        } catch (Exception $e) {
            $errors[] = 'Gagal upload foto: ' . $e->getMessage();
        }
    } elseif (!empty($_POST['image_url'])) {
        $imagePath = trim($_POST['image_url']);
    }

    if (empty($errors)) {
        // Generate unique slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = $baseSlug;
        $counter = 1;
        while (db()->query("SELECT COUNT(*) FROM products WHERE slug = '$slug'")->fetchColumn() > 0) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare("INSERT INTO products 
            (category_id, name, slug, badge, price, stock, netto, description, image, is_active, is_featured, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $inserted = $stmt->execute([
            $categoryId, $name, $slug, $badge, $price, $stock, $netto, $description, $imagePath, $isActive, $isFeatured, $now, $now
        ]);

        if ($inserted) {
            $prodId = (int)db()->lastInsertId();
            // Catat log stok awal
            db()->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, 'in', ?, 'Stok Awal Produk Baru', ?)")
                ->execute([$prodId, $stock, $now]);

            set_flash('success', "Produk '{$name}' berhasil ditambahkan ke katalog!");
            header('Location: ' . base_url('admin/products.php'));
            exit;
        } else {
            $errors[] = 'Gagal menyimpan produk ke database.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card" style="max-width: 820px; margin: 0 auto;">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Formulir Tambah Produk Baru</h3>
    <a href="<?= base_url('admin/products.php') ?>" class="btn-action btn-action-edit">&larr; Kembali</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul style="margin-left: 20px;">
        <?php foreach ($errors as $e): ?>
          <li><?= e($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="<?= base_url('admin/add-product.php') ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodName">Nama Produk <span style="color:red;">*</span></label>
        <input type="text" id="prodName" name="name" class="form-control" placeholder="Contoh: Keripik Sukun Original Fakfak" value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="prodCat">Kategori Produk <span style="color:red;">*</span></label>
        <select id="prodCat" name="category_id" class="form-control" required>
          <option value="">-- Pilih Kategori --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodPrice">Harga Satuan (Rp) <span style="color:red;">*</span></label>
        <input type="number" id="prodPrice" name="price" class="form-control" value="<?= e($_POST['price'] ?? 10000) ?>" min="1000" step="500" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="prodStock">Stok Awal (Bungkus) <span style="color:red;">*</span></label>
        <input type="number" id="prodStock" name="stock" class="form-control" value="<?= e($_POST['stock'] ?? 50) ?>" min="0" required>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodBadge">Label / Badge</label>
        <input type="text" id="prodBadge" name="badge" class="form-control" placeholder="Contoh: Terfavorit, Pedas Gurih, Manis Legit" value="<?= e($_POST['badge'] ?? 'Renyah Gurih') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="prodNetto">Berat Bersih (Netto)</label>
        <input type="text" id="prodNetto" name="netto" class="form-control" placeholder="Contoh: 100 gram" value="<?= e($_POST['netto'] ?? '100 gram') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="prodDesc">Deskripsi Lengkap Produk</label>
      <textarea id="prodDesc" name="description" class="form-control" rows="4" placeholder="Jelaskan keistimewaan rasa, bahan baku khas Fakfak, tekstur, dan saran konsumsinya..."><?= e($_POST['description'] ?? '') ?></textarea>
    </div>

    <!-- Image Upload & Preview -->
    <div class="form-group">
      <label class="form-label" for="productImageInput">Unggah Foto Produk Asli (JPG / PNG / WEBP, maks 3MB)</label>
      <input type="file" id="productImageInput" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
      
      <div style="margin-top: 8px;">
        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Atau gunakan URL gambar web sementara jika belum ada foto lokal:</span>
        <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?= e($_POST['image_url'] ?? '') ?>" style="margin-top: 4px;">
      </div>

      <div class="img-preview-box" style="margin-top: 10px;">
        <img id="imagePreview" src="" alt="Preview Gambar" style="display:none;">
      </div>
    </div>

    <div style="display: flex; gap: 24px; margin: 20px 0;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
        <input type="checkbox" name="is_active" value="1" <?= (!isset($_POST['name']) || isset($_POST['is_active'])) ? 'checked' : '' ?>>
        <span>Status Aktif (Tampil di Toko)</span>
      </label>

      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
        <input type="checkbox" name="is_featured" value="1" <?= (isset($_POST['is_featured'])) ? 'checked' : '' ?>>
        <span>Jadikan Produk Unggulan Beranda</span>
      </label>
    </div>

    <button type="submit" class="btn-action btn-action-primary" style="padding: 12px 28px; font-size: 1rem;">
      Simpan Produk Baru
    </button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


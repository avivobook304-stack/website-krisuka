<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Edit Produk';
$adminNav = 'products';

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: ' . base_url('admin/products.php'));
    exit;
}

$stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan.');
    header('Location: ' . base_url('admin/products.php'));
    exit;
}

$errors = [];
$categories = db()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi keamanan gagal. Silakan coba lagi.';
    }

    $name = sanitize($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $badge = sanitize($_POST['badge'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $newStock = (int)($_POST['stock'] ?? 0);
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

    // Handle Upload Gambar Baru Jika Ada
    $imagePath = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploaded = upload_product_image($_FILES['image']);
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        } catch (Exception $e) {
            $errors[] = 'Gagal upload foto baru: ' . $e->getMessage();
        }
    } elseif (!empty($_POST['image_url'])) {
        $imagePath = trim($_POST['image_url']);
    }

    if (empty($errors)) {
        $now = date('Y-m-d H:i:s');
        $oldStock = (int)$product['stock'];

        $stmtUpdate = db()->prepare("UPDATE products SET 
            category_id = ?, name = ?, badge = ?, price = ?, stock = ?, netto = ?, description = ?, image = ?, is_active = ?, is_featured = ?, updated_at = ?
            WHERE id = ?");
        
        $updated = $stmtUpdate->execute([
            $categoryId, $name, $badge, $price, $newStock, $netto, $description, $imagePath, $isActive, $isFeatured, $now, $productId
        ]);

        if ($updated) {
            // Jika ada perubahan stok, catat di log
            if ($newStock !== $oldStock) {
                $diff = $newStock - $oldStock;
                $logType = ($diff > 0) ? 'in' : 'adjustment';
                db()->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, ?, ?, 'Penyesuaian Manual Edit Produk', ?)")
                    ->execute([$productId, $logType, abs($diff), $now]);
            }

            set_flash('success', "Perubahan data produk '{$name}' berhasil disimpan!");
            header('Location: ' . base_url('admin/products.php'));
            exit;
        } else {
            $errors[] = 'Gagal menyimpan perubahan ke database.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card" style="max-width: 820px; margin: 0 auto;">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Edit Produk: <?= e($product['name']) ?></h3>
      <small style="color: var(--admin-text-muted);">Ubah harga, stok, foto, dan deskripsi produk.</small>
    </div>
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

  <form action="<?= base_url('admin/edit-product.php?id=' . $product['id']) ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodName">Nama Produk <span style="color:red;">*</span></label>
        <input type="text" id="prodName" name="name" class="form-control" value="<?= e($_POST['name'] ?? $product['name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="prodCat">Kategori Produk <span style="color:red;">*</span></label>
        <select id="prodCat" name="category_id" class="form-control" required>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ((isset($_POST['category_id']) ? $_POST['category_id'] : $product['category_id']) == $cat['id']) ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodPrice">Harga Satuan (Rp) <span style="color:red;">*</span></label>
        <input type="number" id="prodPrice" name="price" class="form-control" value="<?= e($_POST['price'] ?? $product['price']) ?>" min="1000" step="500" required>
        <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 4px;">
          Harga saat ini: <strong><?= format_rupiah($product['price']) ?></strong>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="prodStock">Jumlah Stok (Bungkus) <span style="color:red;">*</span></label>
        <input type="number" id="prodStock" name="stock" class="form-control" value="<?= e($_POST['stock'] ?? $product['stock']) ?>" min="0" required>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="prodBadge">Label / Badge</label>
        <input type="text" id="prodBadge" name="badge" class="form-control" value="<?= e($_POST['badge'] ?? $product['badge']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="prodNetto">Berat Bersih (Netto)</label>
        <input type="text" id="prodNetto" name="netto" class="form-control" value="<?= e($_POST['netto'] ?? $product['netto']) ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="prodDesc">Deskripsi Lengkap Produk</label>
      <textarea id="prodDesc" name="description" class="form-control" rows="4"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
    </div>

    <!-- Image Upload & Preview -->
    <div class="form-group">
      <label class="form-label" for="productImageInput">Ganti Foto Produk (Biarkan kosong jika tidak ingin mengganti)</label>
      <input type="file" id="productImageInput" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">

      <div style="margin-top: 8px;">
        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Atau ubah URL foto langsung:</span>
        <input type="url" name="image_url" class="form-control" value="<?= e($product['image']) ?>" style="margin-top: 4px;">
      </div>

      <div style="display: flex; gap: 14px; align-items: center; margin-top: 10px;">
        <div class="img-preview-box">
          <img id="imagePreview" src="<?= e(product_image_url($product['image'])) ?>" alt="Foto Saat Ini">
        </div>
        <small style="color: var(--admin-text-muted);">Foto produk saat ini.</small>
      </div>
    </div>

    <div style="display: flex; gap: 24px; margin: 20px 0;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
        <input type="checkbox" name="is_active" value="1" <?= ($product['is_active']) ? 'checked' : '' ?>>
        <span>Status Aktif (Tampil di Toko)</span>
      </label>

      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
        <input type="checkbox" name="is_featured" value="1" <?= ($product['is_featured']) ? 'checked' : '' ?>>
        <span>Jadikan Produk Unggulan Beranda</span>
      </label>
    </div>

    <button type="submit" class="btn-action btn-action-primary" style="padding: 12px 28px; font-size: 1rem;">
      Simpan Perubahan Produk
    </button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


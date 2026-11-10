<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Kategori Produk';
$adminNav = 'categories';

$errors = [];
$editCategory = null;

// Handle Add / Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if (empty($name)) {
            $errors[] = 'Nama kategori wajib diisi.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $now = date('Y-m-d H:i:s');

            if ($catId > 0) {
                // Update
                $stmt = db()->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $catId]);
                set_flash('success', "Kategori '{$name}' berhasil diperbarui!");
            } else {
                // Insert
                $stmt = db()->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $now]);
                set_flash('success', "Kategori '{$name}' berhasil ditambahkan!");
            }

            header('Location: ' . base_url('admin/categories.php'));
            exit;
        }
    }
}

// Handle Delete Kategori
if (isset($_GET['delete'])) {
    if (verify_csrf()) {
        $delId = (int)$_GET['delete'];
        $productCount = (int)db()->query("SELECT COUNT(*) FROM products WHERE category_id = $delId")->fetchColumn();
        if ($productCount > 0) {
            set_flash('danger', "Kategori tidak dapat dihapus karena masih digunakan oleh $productCount produk.");
        } else {
            db()->prepare("DELETE FROM categories WHERE id = ?")->execute([$delId]);
            set_flash('success', 'Kategori berhasil dihapus.');
        }
    }
    header('Location: ' . base_url('admin/categories.php'));
    exit;
}

// Mode Edit jika ada parameter ?edit=ID
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Ambil semua kategori beserta jumlah produknya
$categories = db()->query("SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.id ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start;">
  <!-- Form Tambah / Edit Kategori -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title"><?= $editCategory ? 'Edit Kategori' : 'Tambah Kategori Baru' ?></h3>
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

    <form action="<?= base_url('admin/categories.php') ?>" method="POST">
      <?= csrf_field() ?>
      <?php if ($editCategory): ?>
        <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="catName">Nama Kategori <span style="color:red;">*</span></label>
        <input type="text" id="catName" name="name" class="form-control" placeholder="Contoh: Keripik Sukun, Keripik Keladi" value="<?= e($editCategory['name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="catDesc">Deskripsi Kategori</label>
        <textarea id="catDesc" name="description" class="form-control" rows="3" placeholder="Jelaskan karakteristik kategori produk ini..."><?= e($editCategory['description'] ?? '') ?></textarea>
      </div>

      <div style="display: flex; gap: 10px;">
        <button type="submit" class="btn-action btn-action-primary" style="padding: 10px 20px;">
          <?= $editCategory ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
        </button>
        <?php if ($editCategory): ?>
          <a href="<?= base_url('admin/categories.php') ?>" class="btn-action btn-action-edit">Batal</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Tabel Kategori -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Daftar Kategori Tersedia</h3>
    </div>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Kategori</th>
            <th>Slug</th>
            <th>Jumlah Produk</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
          <tr>
            <td>#<?= $cat['id'] ?></td>
            <td><strong><?= e($cat['name']) ?></strong></td>
            <td><code><?= e($cat['slug']) ?></code></td>
            <td>
              <span class="badge-status badge-selesai"><?= $cat['product_count'] ?> produk</span>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="<?= base_url('admin/categories.php?edit=' . $cat['id']) ?>" class="btn-action btn-action-edit">
                  ✏️ Edit
                </a>
                <a href="<?= base_url('admin/categories.php?delete=' . $cat['id'] . '&csrf_token=' . csrf_token()) ?>" 
                   class="btn-action btn-action-delete" 
                   onclick="return confirm('Hapus kategori <?= addslashes($cat['name']) ?>?');">
                  🗑️
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


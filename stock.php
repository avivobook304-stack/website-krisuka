<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Manajemen Stok & Inventori';
$adminNav = 'stock';

$errors = [];

// Proses Update Cepat / Tambah Stok
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);
        $type = sanitize($_POST['type'] ?? 'in'); // 'in' = tambah, 'adjustment' = koreksi
        $qty = (int)($_POST['quantity'] ?? 0);
        $note = sanitize($_POST['note'] ?? '');

        if ($productId <= 0 || $qty <= 0) {
            $errors[] = 'Pilih produk dan masukkan jumlah perubahan stok yang valid.';
        } else {
            $now = date('Y-m-d H:i:s');
            $db = db();

            if ($type === 'in') {
                $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$qty, $productId]);
                $db->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, 'in', ?, ?, ?)")
                    ->execute([$productId, $qty, !empty($note) ? $note : 'Restock Stok Baru', $now]);
                set_flash('success', "Berhasil menambahkan $qty bungkus stok produk!");
            } elseif ($type === 'adjustment') {
                // Set langsung
                $db->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$qty, $productId]);
                $db->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, 'adjustment', ?, ?, ?)")
                    ->execute([$productId, $qty, !empty($note) ? $note : 'Penyesuaian Fisik', $now]);
                set_flash('success', "Stok produk berhasil disesuaikan menjadi $qty bungkus!");
            }

            header('Location: ' . base_url('admin/stock.php'));
            exit;
        }
    }
}

// Ambil daftar produk dengan status stok
$products = db()->query("SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.stock ASC")->fetchAll();

// Ambil 15 riwayat log mutasi stok terbaru
$logs = db()->query("SELECT l.*, p.name as product_name 
    FROM stock_logs l 
    LEFT JOIN products p ON l.product_id = p.id 
    ORDER BY l.id DESC 
    LIMIT 15")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 30px; align-items: start; margin-bottom: 30px;">
  <!-- Form Update / Restock Stok Cepat -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Input Mutasi / Restock Stok</h3>
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

    <form action="<?= base_url('admin/stock.php') ?>" method="POST">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="stockProd">Pilih Produk <span style="color:red;">*</span></label>
        <select id="stockProd" name="product_id" class="form-control" required>
          <option value="">-- Pilih Produk --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= e($p['name']) ?> (Sisa: <?= $p['stock'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="stockType">Jenis Aksi</label>
        <select id="stockType" name="type" class="form-control">
          <option value="in">+ Tambah Stok Masuk (Restock Produksi Baru)</option>
          <option value="adjustment">Koreksi Manual (Atur Ulang Total Stok)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="stockQty">Jumlah (Bungkus) <span style="color:red;">*</span></label>
        <input type="number" id="stockQty" name="quantity" class="form-control" placeholder="Contoh: 50" min="1" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="stockNote">Catatan / Keterangan</label>
        <input type="text" id="stockNote" name="note" class="form-control" placeholder="Contoh: Batch goreng pagi ini, opname gudang">
      </div>

      <button type="submit" class="btn-action btn-action-primary" style="padding: 10px 20px; width: 100%; justify-content: center;">
        Simpan Mutasi Stok
      </button>
    </form>
  </div>

  <!-- Status Stok Produk Saat Ini -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Ketersediaan Stok Seluruh Produk</h3>
    </div>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Produk</th>
            <th>Kategori</th>
            <th>Stok Saat Ini</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><strong><?= e($p['name']) ?></strong></td>
            <td><?= e($p['category_name']) ?></td>
            <td><strong><?= (int)$p['stock'] ?> bungkus</strong></td>
            <td>
              <?php if ($p['stock'] <= 5): ?>
                <span class="badge-status badge-dibatalkan">Kritis (&le; 5)</span>
              <?php elseif ($p['stock'] <= 15): ?>
                <span class="badge-status badge-menunggu_konfirmasi">Menipis</span>
              <?php else: ?>
                <span class="badge-status badge-selesai">Aman</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Riwayat Log Mutasi Stok -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">15 Riwayat Log Mutasi Stok Terakhir</h3>
  </div>

  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Produk</th>
          <th>Jenis Mutasi</th>
          <th>Jumlah</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="5" style="text-align: center; padding: 24px;">Belum ada catatan mutasi stok.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td><small><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></small></td>
            <td><strong><?= e($log['product_name'] ?? 'Produk Dihapus') ?></strong></td>
            <td>
              <?php if ($log['type'] === 'in'): ?>
                <span class="badge-status badge-selesai">+ Masuk (In)</span>
              <?php elseif ($log['type'] === 'out'): ?>
                <span class="badge-status badge-dibatalkan">- Keluar (Order)</span>
              <?php else: ?>
                <span class="badge-status badge-diproses">Koreksi</span>
              <?php endif; ?>
            </td>
            <td><strong><?= (int)$log['quantity'] ?> bungkus</strong></td>
            <td><small style="color: var(--admin-text-muted);"><?= e($log['note'] ?? '-') ?></small></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


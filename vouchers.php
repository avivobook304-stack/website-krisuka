<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Kupon & Voucher Diskon';
$adminNav = 'vouchers';

$errors = [];

// Handle Tambah Voucher Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_voucher') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $code = strtoupper(trim(sanitize($_POST['code'] ?? '')));
        $discountType = sanitize($_POST['discount_type'] ?? 'fixed');
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minSpend = (float)($_POST['min_spend'] ?? 0);
        $maxDiscount = (float)($_POST['max_discount'] ?? 0);
        $usageLimit = (int)($_POST['usage_limit'] ?? 100);
        $validUntil = sanitize($_POST['valid_until'] ?? date('Y-12-31'));

        if (empty($code)) {
            $errors[] = 'Kode voucher wajib diisi.';
        } elseif ($discountValue <= 0) {
            $errors[] = 'Nilai diskon harus lebih dari 0.';
        } else {
            // Cek apakah kode sudah digunakan
            $check = db()->prepare("SELECT COUNT(*) FROM vouchers WHERE code = ?");
            $check->execute([$code]);
            if ($check->fetchColumn() > 0) {
                $errors[] = "Kode voucher '$code' sudah ada dalam sistem.";
            } else {
                $stmt = db()->prepare("INSERT INTO vouchers 
                    (code, discount_type, discount_value, min_spend, max_discount, usage_limit, used_count, valid_until, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, 1, ?)");
                $stmt->execute([$code, $discountType, $discountValue, $minSpend, $maxDiscount, $usageLimit, $validUntil, date('Y-m-d H:i:s')]);

                set_flash('success', "Voucher diskon '$code' berhasil dibuat!");
                header('Location: ' . base_url('admin/vouchers.php'));
                exit;
            }
        }
    }
}

// Handle Toggle Status Aktif / Nonaktif
if (isset($_GET['toggle_id'])) {
    if (verify_csrf()) {
        $toggleId = (int)$_GET['toggle_id'];
        db()->prepare("UPDATE vouchers SET is_active = (1 - is_active) WHERE id = ?")->execute([$toggleId]);
        set_flash('info', 'Status aktifasi voucher berhasil diubah.');
    }
    header('Location: ' . base_url('admin/vouchers.php'));
    exit;
}

// Handle Hapus Voucher
if (isset($_GET['delete_id'])) {
    if (verify_csrf()) {
        $delId = (int)$_GET['delete_id'];
        db()->prepare("DELETE FROM vouchers WHERE id = ?")->execute([$delId]);
        set_flash('success', 'Voucher berhasil dihapus.');
    }
    header('Location: ' . base_url('admin/vouchers.php'));
    exit;
}

// Ambil semua data voucher
$vouchers = db()->query("SELECT * FROM vouchers ORDER BY id DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 30px; align-items: start;">
  <!-- Form Tambah Voucher Baru -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Buat Kode Voucher Baru</h3>
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

    <form action="<?= base_url('admin/vouchers.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_voucher">

      <div class="form-group">
        <label class="form-label" for="vCode">Kode Kupon / Voucher <span style="color:red;">*</span></label>
        <input type="text" id="vCode" name="code" class="form-control" placeholder="Contoh: HEMAT5RB, KRISUKAFK" style="text-transform: uppercase; font-weight: 700; letter-spacing: 1px;" required>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="vType">Tipe Diskon <span style="color:red;">*</span></label>
          <select id="vType" name="discount_type" class="form-control" onchange="updateDiscountLabel(this.value)">
            <option value="fixed">Potongan Tetap (Nominal Rp)</option>
            <option value="percent">Persentase (%)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="vVal" id="valLabel">Besar Potongan (Rp) <span style="color:red;">*</span></label>
          <input type="number" id="vVal" name="discount_value" class="form-control" placeholder="Contoh: 5000" min="1" required>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="vMin">Minimal Belanja (Rp)</label>
          <input type="number" id="vMin" name="min_spend" class="form-control" value="20000" min="0">
        </div>

        <div class="form-group">
          <label class="form-label" for="vMax">Maks. Potongan (Khusus %)</label>
          <input type="number" id="vMax" name="max_discount" class="form-control" value="10000" min="0">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="vLimit">Batas Kuota Pemakaian</label>
          <input type="number" id="vLimit" name="usage_limit" class="form-control" value="100" min="1">
        </div>

        <div class="form-group">
          <label class="form-label" for="vDate">Berlaku Sampai Tanggal</label>
          <input type="date" id="vDate" name="valid_until" class="form-control" value="<?= date('Y-12-31') ?>" required>
        </div>
      </div>

      <button type="submit" class="btn-action btn-action-primary" style="padding: 12px 20px; width: 100%; justify-content: center;">
        + Terbitkan Voucher Diskon
      </button>
    </form>
  </div>

  <!-- Daftar Voucher yang Ada -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Daftar Voucher Promo Toko</h3>
    </div>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Diskon</th>
            <th>Min. Belanja</th>
            <th>Terpakai</th>
            <th>Kadaluarsa</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vouchers)): ?>
            <tr><td colspan="7" style="text-align: center; padding: 30px;">Belum ada voucher aktif.</td></tr>
          <?php else: ?>
            <?php foreach ($vouchers as $v): ?>
            <tr>
              <td><strong style="color: var(--admin-primary); font-size: 1rem; letter-spacing: 0.5px;"><code><?= e($v['code']) ?></code></strong></td>
              <td>
                <span class="badge-status badge-selesai" style="font-weight: 800;">
                  <?= ($v['discount_type'] === 'fixed') ? format_rupiah($v['discount_value']) : ((float)$v['discount_value'] . '%') ?>
                </span>
              </td>
              <td><?= format_rupiah($v['min_spend']) ?></td>
              <td><?= (int)$v['used_count'] ?> / <?= (int)$v['usage_limit'] ?></td>
              <td><small><?= date('d M Y', strtotime($v['valid_until'])) ?></small></td>
              <td>
                <?php if ($v['is_active']): ?>
                  <span class="badge-status badge-selesai">Aktif</span>
                <?php else: ?>
                  <span class="badge-status badge-dibatalkan">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <a href="<?= base_url('admin/vouchers.php?toggle_id=' . $v['id'] . '&csrf_token=' . csrf_token()) ?>" 
                     class="btn-action btn-action-edit" 
                     title="Ubah Status Aktif">
                    <?= $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                  </a>
                  <a href="<?= base_url('admin/vouchers.php?delete_id=' . $v['id'] . '&csrf_token=' . csrf_token()) ?>" 
                     class="btn-action btn-action-delete" 
                     onclick="return confirm('Hapus voucher <?= e($v['code']) ?>?');" 
                     title="Hapus Voucher">
                    🗑️
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
</div>

<script>
function updateDiscountLabel(type) {
  const lbl = document.getElementById('valLabel');
  if (type === 'percent') {
    lbl.innerHTML = 'Besar Potongan (%) <span style="color:red;">*</span>';
  } else {
    lbl.innerHTML = 'Besar Potongan (Rp) <span style="color:red;">*</span>';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


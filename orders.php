<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Manajemen Pesanan';
$adminNav = 'orders';

$statusFilter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $sql .= " AND order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Hitung status counters
$counts = [
    'all' => (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'menunggu_konfirmasi' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'menunggu_konfirmasi'")->fetchColumn(),
    'dikonfirmasi' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dikonfirmasi'")->fetchColumn(),
    'diproses' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'diproses'")->fetchColumn(),
    'siap_dikirim' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'siap_dikirim'")->fetchColumn(),
    'selesai' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'selesai'")->fetchColumn(),
    'dibatalkan' => (int)db()->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dibatalkan'")->fetchColumn()
];

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Daftar Transaksi Pesanan Pelanggan</h3>
      <small style="color: var(--admin-text-muted);">Kelola status konfirmasi pembayaran, pemrosesan dapur, dan pengiriman.</small>
    </div>
  </div>

  <!-- Status Filter Pills -->
  <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
    <a href="<?= base_url('admin/orders.php') ?>" class="filter-pill <?= empty($statusFilter) || $statusFilter === 'all' ? 'active' : '' ?>">
      Semua (<?= $counts['all'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=menunggu_konfirmasi') ?>" class="filter-pill <?= ($statusFilter === 'menunggu_konfirmasi') ? 'active' : '' ?>">
      Menunggu Konfirmasi (<?= $counts['menunggu_konfirmasi'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=dikonfirmasi') ?>" class="filter-pill <?= ($statusFilter === 'dikonfirmasi') ? 'active' : '' ?>">
      Dikonfirmasi (<?= $counts['dikonfirmasi'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=diproses') ?>" class="filter-pill <?= ($statusFilter === 'diproses') ? 'active' : '' ?>">
      Diproses (<?= $counts['diproses'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=siap_dikirim') ?>" class="filter-pill <?= ($statusFilter === 'siap_dikirim') ? 'active' : '' ?>">
      Siap Dikirim (<?= $counts['siap_dikirim'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=selesai') ?>" class="filter-pill <?= ($statusFilter === 'selesai') ? 'active' : '' ?>">
      Selesai (<?= $counts['selesai'] ?>)
    </a>
    <a href="<?= base_url('admin/orders.php?status=dibatalkan') ?>" class="filter-pill <?= ($statusFilter === 'dibatalkan') ? 'active' : '' ?>">
      Dibatalkan (<?= $counts['dibatalkan'] ?>)
    </a>
  </div>

  <!-- Search Input -->
  <form action="<?= base_url('admin/orders.php') ?>" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px;">
    <?php if (!empty($statusFilter)): ?>
      <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
    <?php endif; ?>
    <input type="text" name="q" class="form-control" placeholder="Cari Kode Pesanan, Nama Pembeli, atau No. WA..." value="<?= e($search) ?>" style="max-width: 400px;">
    <button type="submit" class="btn-action btn-action-primary">Cari</button>
    <?php if (!empty($search)): ?>
      <a href="<?= base_url('admin/orders.php' . (!empty($statusFilter) ? '?status=' . urlencode($statusFilter) : '')) ?>" class="btn-action btn-action-edit">Reset</a>
    <?php endif; ?>
  </form>

  <!-- Orders Table -->
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Kode Pesanan</th>
          <th>Tanggal &amp; Jam</th>
          <th>Pembeli</th>
          <th>Kontak WA</th>
          <th>Total Item</th>
          <th>Total Bayar</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px; color: var(--admin-text-muted);">
              Tidak ada data pesanan yang sesuai filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $ord): ?>
          <tr>
            <td>
              <a href="<?= base_url('admin/order-detail.php?id=' . $ord['id']) ?>" style="font-weight: 700; color: var(--admin-primary);">
                #<?= e($ord['order_code']) ?>
              </a>
            </td>
            <td><small><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></small></td>
            <td><strong><?= e($ord['customer_name']) ?></strong></td>
            <td>
              <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['customer_phone']) ?>" target="_blank" style="color: #15803D; font-weight: 600;">
                <?= e($ord['customer_phone']) ?>
              </a>
            </td>
            <td><?= (int)$ord['total_qty'] ?> bks</td>
            <td>
              <strong style="color: var(--admin-primary);"><?= format_rupiah($ord['total_price']) ?></strong>
              <div style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 2px;">
                <?= e(payment_method_label($ord['payment_method'])) ?>
              </div>
            </td>
            <td>
              <span class="badge-status badge-<?= e($ord['order_status']) ?>">
                <?= ucwords(str_replace('_', ' ', $ord['order_status'])) ?>
              </span>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="<?= base_url('admin/order-detail.php?id=' . $ord['id']) ?>" class="btn-action btn-action-view" title="Detail & Kelola Status">
                  Kelola
                </a>
                <a href="<?= base_url('admin/invoice.php?id=' . $ord['id']) ?>" target="_blank" class="btn-action btn-action-edit" title="Cetak Bukti Pesanan">
                  🖨️
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

<?php include __DIR__ . '/includes/footer.php'; ?>


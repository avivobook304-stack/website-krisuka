<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Data Pelanggan';
$adminNav = 'customers';

$search = sanitize($_GET['q'] ?? '');

$sql = "SELECT 
            customer_name,
            customer_phone,
            MAX(customer_address) as latest_address,
            COUNT(id) as total_orders,
            SUM(total_price) as total_spend,
            MAX(created_at) as last_order_date
        FROM orders 
        WHERE order_status != 'dibatalkan'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (customer_name LIKE ? OR customer_phone LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY customer_phone ORDER BY total_spend DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Daftar Pelanggan KRISUKA</h3>
      <small style="color: var(--admin-text-muted);">Data pembeli yang terakumulasi dari seluruh transaksi pesanan aktif.</small>
    </div>
  </div>

  <!-- Search Filter -->
  <form action="<?= base_url('admin/customers.php') ?>" method="GET" style="display: flex; gap: 12px; margin-bottom: 24px;">
    <input type="text" name="q" class="form-control" placeholder="Cari nama atau nomor WhatsApp pembeli..." value="<?= e($search) ?>" style="max-width: 400px;">
    <button type="submit" class="btn-action btn-action-primary">Cari</button>
    <?php if (!empty($search)): ?>
      <a href="<?= base_url('admin/customers.php') ?>" class="btn-action btn-action-edit">Reset</a>
    <?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Nama Pelanggan</th>
          <th>Kontak WhatsApp</th>
          <th>Alamat Pengiriman Terakhir</th>
          <th>Total Transaksi</th>
          <th>Akumulasi Belanja</th>
          <th>Pesanan Terakhir</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: var(--admin-text-muted);">
              Belum ada data pelanggan yang tercatat.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
          <?php $cleanPhone = preg_replace('/[^0-9]/', '', $c['customer_phone']); ?>
          <tr>
            <td><strong><?= e($c['customer_name']) ?></strong></td>
            <td>
              <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" style="color: #15803D; font-weight: 700;">
                <?= e($c['customer_phone']) ?>
              </a>
            </td>
            <td>
              <small style="color: var(--admin-text-muted); display: block; max-width: 280px;">
                <?= e($c['latest_address']) ?>
              </small>
            </td>
            <td>
              <span class="badge-status badge-selesai"><?= (int)$c['total_orders'] ?> pesanan</span>
            </td>
            <td><strong style="color: var(--admin-primary);"><?= format_rupiah($c['total_spend']) ?></strong></td>
            <td><small><?= date('d/m/Y H:i', strtotime($c['last_order_date'])) ?></small></td>
            <td>
              <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode("Halo {$c['customer_name']}, terima kasih telah menjadi pelanggan setia KRISUKA Fakfak...") ?>" 
                 target="_blank" 
                 class="btn-action btn-action-view" 
                 style="font-size: 0.8rem;">
                💬 Hubungi
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


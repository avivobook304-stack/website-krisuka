<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Laporan Penjualan';
$adminNav = 'reports';

$period = sanitize($_GET['period'] ?? 'this_month');
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'));

// Tentukan rentang tanggal berdasarkan filter cepat
switch ($period) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d');
        break;
    case 'this_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-d');
        break;
    case 'custom':
        // Gunakan parameter start_date dan end_date yang diisi user
        break;
}

$db = db();

// Query Transaksi pada Rentang Tanggal
$sql = "SELECT o.* 
        FROM orders o 
        WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? AND o.order_status != 'dibatalkan' 
        ORDER BY o.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$reportOrders = $stmt->fetchAll();

// Hitung Ringkasan
$totalTransactions = count($reportOrders);
$totalRevenue = 0.00;
$totalUnitsSold = 0;

foreach ($reportOrders as $ro) {
    $totalRevenue += (float)$ro['total_price'];
    $totalUnitsSold += (int)$ro['total_qty'];
}

// Query Produk Terlaris pada Periode
$sqlTop = "SELECT oi.product_name, SUM(oi.qty) as total_qty, SUM(oi.subtotal) as total_sales 
           FROM order_items oi 
           JOIN orders o ON oi.order_id = o.id 
           WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? AND o.order_status != 'dibatalkan' 
           GROUP BY oi.product_name 
           ORDER BY total_qty DESC";
$stmtTop = $db->prepare($sqlTop);
$stmtTop->execute([$startDate, $endDate]);
$topSoldProducts = $stmtTop->fetchAll();

// Handle Export ke CSV / Excel
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "laporan_penjualan_krisuka_{$startDate}_sampai_{$endDate}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // UTF-8 BOM untuk Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['LAPORAN PENJUALAN KRISUKA']);
    fputcsv($output, ['Periode:', $startDate . ' s/d ' . $endDate]);
    fputcsv($output, ['Total Transaksi:', $totalTransactions]);
    fputcsv($output, ['Total Produk Terjual:', $totalUnitsSold . ' bungkus']);
    fputcsv($output, ['Total Omzet:', format_rupiah($totalRevenue)]);
    fputcsv($output, []); // Baris kosong

    fputcsv($output, ['No.', 'Kode Pesanan', 'Tanggal', 'Nama Pembeli', 'WhatsApp', 'Total Item', 'Total Pembayaran', 'Status']);

    foreach ($reportOrders as $idx => $r) {
        fputcsv($output, [
            $idx + 1,
            $r['order_code'],
            $r['created_at'],
            $r['customer_name'],
            $r['customer_phone'],
            $r['total_qty'],
            $r['total_price'],
            $r['order_status']
        ]);
    }
    fclose($output);
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Filter Laporan Penjualan</h3>
      <small style="color: var(--admin-text-muted);">Pilih periode waktu atau rentang tanggal kustom untuk melihat rekap omzet.</small>
    </div>

    <!-- Export Buttons -->
    <div style="display: flex; gap: 10px;">
      <a href="<?= base_url('admin/reports.php?period=custom&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&export=csv') ?>" class="btn-action btn-action-edit">
        📥 Unduh Excel (CSV)
      </a>
      <button type="button" class="btn-action btn-action-primary" onclick="window.print();">
        🖨️ Cetak Laporan
      </button>
    </div>
  </div>

  <!-- Form Filter Periode -->
  <form action="<?= base_url('admin/reports.php') ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 24px;">
    <div>
      <label class="form-label" for="periodSelect">Pilihan Periode:</label>
      <select id="periodSelect" name="period" class="form-control" onchange="if(this.value !== 'custom') this.form.submit();">
        <option value="today" <?= ($period === 'today') ? 'selected' : '' ?>>Hari Ini (<?= date('d M') ?>)</option>
        <option value="this_week" <?= ($period === 'this_week') ? 'selected' : '' ?>>Minggu Ini</option>
        <option value="this_month" <?= ($period === 'this_month') ? 'selected' : '' ?>>Bulan Ini (<?= date('F Y') ?>)</option>
        <option value="this_year" <?= ($period === 'this_year') ? 'selected' : '' ?>>Tahun Ini (<?= date('Y') ?>)</option>
        <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Rentang Tanggal Kustom...</option>
      </select>
    </div>

    <div>
      <label class="form-label" for="startDateInput">Dari Tanggal:</label>
      <input type="date" id="startDateInput" name="start_date" class="form-control" value="<?= e($startDate) ?>">
    </div>

    <div>
      <label class="form-label" for="endDateInput">Sampai Tanggal:</label>
      <input type="date" id="endDateInput" name="end_date" class="form-control" value="<?= e($endDate) ?>">
    </div>

    <button type="submit" class="btn-action btn-action-primary" style="padding: 10px 20px;">
      Terapkan
    </button>
  </form>

  <!-- Summary Metric Cards for this Period -->
  <div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
      <div class="stat-icon-wrapper blue">📊</div>
      <div class="stat-data">
        <small>Jumlah Transaksi</small>
        <h3><?= $totalTransactions ?></h3>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon-wrapper green">📦</div>
      <div class="stat-data">
        <small>Total Produk Terjual</small>
        <h3><?= $totalUnitsSold ?> bks</h3>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon-wrapper gold">💰</div>
      <div class="stat-data">
        <small>Total Omzet Periode Ini</small>
        <h3 style="font-size: 1.35rem;"><?= format_rupiah($totalRevenue) ?></h3>
      </div>
    </div>
  </div>

  <!-- Detailed Transactions Table -->
  <h4 class="admin-card-title" style="margin-bottom: 14px;">Rincian Transaksi Pesanan (<?= $startDate ?> s/d <?= $endDate ?>)</h4>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Kode Pesanan</th>
          <th>Tanggal &amp; Waktu</th>
          <th>Nama Pelanggan</th>
          <th>WhatsApp</th>
          <th>Total Item</th>
          <th>Total Omzet</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reportOrders)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 30px; color: var(--admin-text-muted);">
              Tidak ada data transaksi pada rentang tanggal yang dipilih.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($reportOrders as $ro): ?>
          <tr>
            <td>
              <a href="<?= base_url('admin/order-detail.php?id=' . $ro['id']) ?>" target="_blank" style="font-weight: 700; color: var(--admin-primary);">
                #<?= e($ro['order_code']) ?>
              </a>
            </td>
            <td><small><?= date('d/m/Y H:i', strtotime($ro['created_at'])) ?></small></td>
            <td><strong><?= e($ro['customer_name']) ?></strong></td>
            <td><?= e($ro['customer_phone']) ?></td>
            <td><?= (int)$ro['total_qty'] ?> bks</td>
            <td><strong><?= format_rupiah($ro['total_price']) ?></strong></td>
            <td>
              <span class="badge-status badge-<?= e($ro['order_status']) ?>">
                <?= ucwords(str_replace('_', ' ', $ro['order_status'])) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


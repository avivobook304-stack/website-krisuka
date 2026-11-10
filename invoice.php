<?php
require_once __DIR__ . '/../config/functions.php';

require_admin();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    die("ID Pesanan tidak valid.");
}

$stmt = db()->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

$stmtItems = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$storeName = get_setting('store_name', 'KRISUKA');
$storeAbbr = get_setting('store_abbreviation', 'Keripik Sukun dan Keladi');
$storeOrigin = get_setting('store_origin', 'Fakfak, Papua Barat');
$storeWa = get_setting('whatsapp_number', '6282274761719');
$storeAddress = get_setting('store_address', 'Fakfak, Papua Barat, Indonesia');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Pesanan #<?= e($order['order_code']) ?> | <?= e($storeName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', Arial, sans-serif;
      color: #1E2922;
      background: #FFFFFF;
      margin: 0;
      padding: 30px;
      font-size: 14px;
      line-height: 1.5;
    }
    .invoice-container {
      max-width: 750px;
      margin: 0 auto;
      border: 1px solid #E2E8E5;
      padding: 40px;
      border-radius: 12px;
    }
    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #1B4D3E;
      padding-bottom: 24px;
      margin-bottom: 24px;
    }
    .brand-title {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 2rem;
      color: #12372A;
      margin: 0 0 4px;
    }
    .brand-meta {
      font-size: 0.85rem;
      color: #64748B;
    }
    .invoice-info {
      text-align: right;
    }
    .invoice-info h3 {
      margin: 0 0 6px;
      font-size: 1.25rem;
      color: #1B4D3E;
    }
    .customer-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 30px;
      background: #F8FAFC;
      padding: 18px 20px;
      border-radius: 8px;
    }
    .table-invoice {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }
    .table-invoice th {
      background: #1B4D3E;
      color: #FFFFFF;
      padding: 10px 14px;
      text-align: left;
      font-size: 0.88rem;
    }
    .table-invoice td {
      padding: 12px 14px;
      border-bottom: 1px solid #E2E8E5;
    }
    .table-invoice tfoot th {
      background: transparent;
      color: #1B4D3E;
      font-size: 1.15rem;
      padding: 14px;
    }
    .actions-bar {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      max-width: 750px;
      margin: 0 auto 20px;
    }
    .btn-print {
      background: #1B4D3E;
      color: #FFFFFF;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
    }
    @media print {
      .actions-bar { display: none; }
      body { padding: 0; }
      .invoice-container { border: none; padding: 0; }
    }
  </style>
</head>
<body>

  <div class="actions-bar">
    <button class="btn-print" onclick="window.print();">🖨️ Cetak / Simpan PDF</button>
    <button class="btn-print" onclick="window.close();" style="background: #64748B;">✕ Tutup Tab</button>
  </div>

  <div class="invoice-container">
    <div class="invoice-header">
      <div>
        <h1 class="brand-title"><?= e($storeName) ?></h1>
        <div class="brand-meta">
          <strong><?= e($storeAbbr) ?></strong><br>
          <?= e($storeAddress) ?><br>
          WhatsApp: +<?= e($storeWa) ?>
        </div>
      </div>

      <div class="invoice-info">
        <h3>BUKTI PEMESANAN</h3>
        <div>Kode: <strong>#<?= e($order['order_code']) ?></strong></div>
        <div>Tanggal: <?= date('d M Y, H:i', strtotime($order['created_at'])) ?> WIT</div>
        <div style="margin-top: 6px;">
          Status: <strong><?= strtoupper(str_replace('_', ' ', $order['order_status'])) ?></strong>
        </div>
      </div>
    </div>

    <div class="customer-grid">
      <div>
        <strong style="color: #64748B; font-size: 0.8rem; text-transform: uppercase;">Tujuan Pengiriman:</strong>
        <div style="font-weight: 700; font-size: 1.05rem; margin-top: 4px;"><?= e($order['customer_name']) ?></div>
        <div>WhatsApp: <?= e($order['customer_phone']) ?></div>
        <div style="margin-top: 6px;"><?= nl2br(e($order['customer_address'])) ?></div>
        <?php if (preg_match('/https:\/\/maps\.google\.com\/\?q=[^ \n\r\t]+/', $order['customer_address'], $gpsMatch)): ?>
          <div style="margin-top: 6px;">
            <a href="<?= e($gpsMatch[0]) ?>" target="_blank" style="color: #0284C7; font-weight: 700; text-decoration: underline; font-size: 0.82rem;">
              🗺️ Buka Titik Peta GPS Google Maps
            </a>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <strong style="color: #64748B; font-size: 0.8rem; text-transform: uppercase;">Catatan &amp; Pembayaran:</strong>
        <div style="margin-top: 4px;">Metode Bayar: <strong><?= e(payment_method_label($order['payment_method'])) ?></strong></div>
        <?php if (!empty($order['customer_notes'])): ?>
          <div style="margin-top: 6px;"><em>"<?= e($order['customer_notes']) ?>"</em></div>
        <?php endif; ?>
      </div>
    </div>

    <table class="table-invoice">
      <thead>
        <tr>
          <th>No.</th>
          <th>Nama Produk Keripik</th>
          <th style="text-align: right;">Harga Satuan</th>
          <th style="text-align: center;">Jumlah</th>
          <th style="text-align: right;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td><?= $idx + 1 ?></td>
          <td><strong><?= e($it['product_name']) ?></strong></td>
          <td style="text-align: right;"><?= format_rupiah($it['price']) ?></td>
          <td style="text-align: center;"><?= (int)$it['qty'] ?></td>
          <td style="text-align: right; font-weight: 700;"><?= format_rupiah($it['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <?php 
        $calcSubtotal = 0;
        foreach ($items as $it) { $calcSubtotal += (float)$it['subtotal']; }
        $discountVal = (float)($order['discount_amount'] ?? 0);
        $shippingVal = (float)($order['shipping_cost'] ?? 0);
        $hasBreakdown = ($discountVal > 0 || $shippingVal > 0 || !empty($order['voucher_code']) || !empty($order['shipping_zone']));
        ?>
        <?php if ($hasBreakdown): ?>
        <tr>
          <th colspan="4" style="text-align: right; font-weight: normal; color: #64748B;">Subtotal Produk:</th>
          <th style="text-align: right;"><?= format_rupiah($calcSubtotal) ?></th>
        </tr>
        <?php if ($discountVal > 0 || !empty($order['voucher_code'])): ?>
        <tr>
          <th colspan="4" style="text-align: right; font-weight: normal; color: #15803D;">Diskon Kupon <?= !empty($order['voucher_code']) ? '(' . e($order['voucher_code']) . ')' : '' ?>:</th>
          <th style="text-align: right; color: #15803D;">-<?= format_rupiah($discountVal) ?></th>
        </tr>
        <?php endif; ?>
        <tr>
          <th colspan="4" style="text-align: right; font-weight: normal; color: #64748B;">Ongkos Kirim <?= !empty($order['shipping_zone']) ? '(' . e($order['shipping_zone']) . ')' : '' ?>:</th>
          <th style="text-align: right;"><?= ($shippingVal > 0) ? format_rupiah($shippingVal) : 'GRATIS' ?></th>
        </tr>
        <?php endif; ?>
        <tr style="border-top: 2px solid #1B4D3E;">
          <th colspan="4" style="text-align: right; font-size: 1.05rem;">Total Pembayaran:</th>
          <th style="text-align: right; font-size: 1.15rem; color: #1B4D3E;"><?= format_rupiah($order['total_price']) ?></th>
        </tr>
      </tfoot>
    </table>

    <div style="text-align: center; padding-top: 20px; border-top: 1px dashed #E2E8E5; font-size: 0.82rem; color: #64748B;">
      Terima kasih telah berbelanja keripik lokal khas Fakfak di <strong><?= e($storeName) ?></strong>.<br>
      <em>"Renyah, Lezat, Cita Rasa Lokal."</em>
    </div>
  </div>

</body>
</html>


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
$storeAbbr = get_setting('store_abbreviation', 'Keripik Sukun & Keladi Fakfak');
$storeWa = get_setting('whatsapp_number', '082274761719');
$storeAddress = get_setting('store_address', 'Kabupaten Fakfak, Papua Barat');

$cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
$totalQty = 0;
foreach ($items as $it) {
    $totalQty += (int)$it['qty'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Label Pengiriman #<?= e($order['order_code']) ?> - <?= e($storeName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>
    @page {
      size: A6 portrait;
      margin: 6mm;
    }
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Plus Jakarta Sans', Arial, sans-serif;
      color: #111827;
      background: #F1F5F9;
      margin: 0;
      padding: 20px;
      font-size: 11px;
      line-height: 1.35;
    }
    .label-box {
      width: 100%;
      max-width: 400px;
      margin: 0 auto;
      background: #FFFFFF;
      border: 2px solid #1E293B;
      padding: 12px;
      border-radius: 6px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .label-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #1E293B;
      padding-bottom: 8px;
      margin-bottom: 8px;
    }
    .brand-logo-text {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 1.3rem;
      font-weight: 800;
      color: #12372A;
      line-height: 1;
    }
    .brand-sub {
      font-size: 8.5px;
      color: #475569;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .order-badge-box {
      text-align: right;
    }
    .order-code {
      font-size: 12px;
      font-weight: 800;
      color: #1E293B;
      font-family: monospace;
    }
    .order-date {
      font-size: 8.5px;
      color: #64748B;
    }
    .section-box {
      border: 1px solid #CBD5E1;
      border-radius: 4px;
      padding: 8px;
      margin-bottom: 8px;
    }
    .section-title {
      font-size: 9px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #475569;
      margin-bottom: 4px;
      border-bottom: 1px dashed #E2E8F0;
      padding-bottom: 2px;
    }
    .recipient-name {
      font-size: 13px;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 2px;
    }
    .recipient-phone {
      font-size: 11px;
      font-weight: 700;
      color: #1E293B;
      margin-bottom: 4px;
    }
    .recipient-address {
      font-size: 10px;
      color: #334155;
      line-height: 1.4;
      white-space: pre-line;
    }
    .payment-badge-strip {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 6px 8px;
      border-radius: 4px;
      margin-bottom: 8px;
      font-weight: 700;
    }
    .cod-strip {
      background: #FEF3C7;
      border: 1.5px dashed #D97706;
      color: #92400E;
    }
    .non-cod-strip {
      background: #DCFCE7;
      border: 1px solid #16A34A;
      color: #166534;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9.5px;
      margin-bottom: 8px;
    }
    .items-table th {
      background: #F8FAFC;
      border: 1px solid #CBD5E1;
      padding: 4px 6px;
      text-align: left;
      font-size: 8.5px;
    }
    .items-table td {
      border: 1px solid #E2E8F0;
      padding: 4px 6px;
    }
    .footer-barcodes {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 2px solid #1E293B;
      padding-top: 6px;
      font-size: 8px;
      color: #64748B;
    }
    .barcode-lines {
      font-family: monospace;
      font-size: 18px;
      letter-spacing: 2px;
      color: #0F172A;
      line-height: 1;
    }
    .no-print-bar {
      max-width: 400px;
      margin: 0 auto 14px;
      display: flex;
      gap: 10px;
      justify-content: space-between;
      align-items: center;
    }
    .btn-print {
      background: #1B4D3E;
      color: #FFFFFF;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      font-weight: 700;
      cursor: pointer;
      font-size: 12px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-back {
      background: #E2E8F0;
      color: #334155;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 12px;
    }

    @media print {
      body {
        background: #FFFFFF;
        padding: 0;
      }
      .no-print-bar {
        display: none !important;
      }
      .label-box {
        border: 2px solid #000000;
        box-shadow: none;
        max-width: 100%;
        padding: 8px;
      }
    }
  </style>
</head>
<body>

<div class="no-print-bar">
  <a href="<?= base_url('admin/order-detail.php?id=' . $order['id']) ?>" class="btn-back">&larr; Kembali</a>
  <button onclick="window.print()" class="btn-print">
    🖨️ Cetak Label (A6)
  </button>
</div>

<div class="label-box">
  <!-- Header -->
  <div class="label-header">
    <div>
      <div class="brand-logo-text">KRISUKA</div>
      <div class="brand-sub">Khas Fakfak, Papua Barat</div>
    </div>
    <div class="order-badge-box">
      <div class="order-code">#<?= e($order['order_code']) ?></div>
      <div class="order-date"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?> WIT</div>
    </div>
  </div>

  <!-- Penerima (Customer) -->
  <div class="section-box" style="border-left: 4px solid #1B4D3E;">
    <div class="section-title">PENERIMA:</div>
    <div class="recipient-name"><?= e($order['customer_name']) ?></div>
    <div class="recipient-phone">📞 <?= e($order['customer_phone']) ?></div>
    <div class="recipient-address"><?= e($order['customer_address']) ?></div>
    <?php if (!empty($order['notes'])): ?>
      <div style="margin-top: 4px; font-size: 9px; color: #B45309; font-style: italic;">
        Catatan: <?= e($order['notes']) ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pengirim (Store) -->
  <div class="section-box" style="background: #F8FAFC;">
    <div class="section-title">PENGIRIM:</div>
    <div style="font-weight: 700; color: #0F172A; font-size: 11px;">KRISUKA Fakfak (Oleh-Oleh Khas Fakfak)</div>
    <div style="font-size: 9.5px; color: #475569;">📞 <?= e($storeWa) ?> &bull; Fakfak, Papua Barat</div>
  </div>

  <!-- Status Pembayaran / COD -->
  <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
    <div class="payment-badge-strip cod-strip">
      <span>💵 METODE: COD (BAYAR DI TEMPAT)</span>
      <span style="font-size: 12px; font-weight: 800;">TAGIH: <?= format_rupiah($order['total_price']) ?></span>
    </div>
  <?php else: ?>
    <div class="payment-badge-strip non-cod-strip">
      <span>✅ METODE: <?= strtoupper(e(payment_method_label($order['payment_method'] ?? 'bank_transfer'))) ?></span>
      <span>LUNAS (NON-COD)</span>
    </div>
  <?php endif; ?>

  <!-- Item Checklist -->
  <table class="items-table">
    <thead>
      <tr>
        <th style="width: 20px; text-align: center;">✓</th>
        <th>Isi Paket (Varian Keripik)</th>
        <th style="width: 35px; text-align: center;">Qty</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td style="text-align: center; font-size: 11px;">☐</td>
        <td><strong><?= e($it['product_name']) ?></strong></td>
        <td style="text-align: center; font-weight: 700;"><?= (int)$it['qty'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background: #F8FAFC; font-weight: 700;">
        <td colspan="2" style="text-align: right; padding-right: 8px;">Total Isi Paket:</td>
        <td style="text-align: center;"><?= $totalQty ?> bks</td>
      </tr>
    </tfoot>
  </table>

  <!-- Footer Tracking -->
  <div class="footer-barcodes">
    <div>
      <div style="font-weight: 700; color: #1E293B;">PENGIRIMAN: <?= strtoupper(e($order['shipping_zone'] ?? 'Fakfak / Reguler')) ?></div>
      <div>Terima kasih atas pesanan Anda di KRISUKA!</div>
    </div>
    <div style="text-align: right;">
      <div class="barcode-lines">||| | |||| | |||||</div>
      <div style="font-family: monospace; font-size: 8px;"><?= e($order['order_code']) ?></div>
    </div>
  </div>
</div>

</body>
</html>


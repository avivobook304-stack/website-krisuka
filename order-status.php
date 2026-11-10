<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'order-status';
$orderCode = sanitize($_GET['order_code'] ?? '');
$openWa = isset($_GET['wa']) && $_GET['wa'] == 1;

$order = null;
$orderItems = [];

if (!empty($orderCode)) {
    $stmt = db()->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();

    if ($order) {
        $stmtItems = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order['id']]);
        $orderItems = $stmtItems->fetchAll();
    }
}

$pageTitle = 'Status Pesanan ' . ($order ? '#' . $order['order_code'] : '') . ' | KRISUKA Fakfak';

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 30px 0; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <h1 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-primary-dark); margin-bottom: 6px;">
      Pelacakan &amp; Bukti Pesanan
    </h1>
    <p style="color: var(--color-text-muted); font-size: 0.95rem;">
      Pantau status pemrosesan pesanan keripik KRISUKA Anda secara real-time.
    </p>
  </div>
</div>

<section style="padding: 50px 0 80px;">
  <div class="container" style="max-width: 800px;">
    <!-- Search Bar Order Code -->
    <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 20px 24px; box-shadow: var(--shadow-sm); margin-bottom: 30px;">
      <form action="<?= base_url('order-status.php') ?>" method="GET" style="display: flex; gap: 12px; align-items: center;">
        <input 
          type="text" 
          name="order_code" 
          class="form-control" 
          placeholder="Masukkan Kode Pesanan Anda (contoh: KSU-20261110-XXXX)..." 
          value="<?= e($orderCode) ?>" 
          required
          style="flex-grow: 1;"
        >
        <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
          Lacak
        </button>
      </form>
    </div>

    <?php if (!empty($orderCode) && !$order): ?>
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px dashed var(--color-cream-border); padding: 50px 20px; text-align: center;">
        <span style="font-size: 3rem; display: block; margin-bottom: 12px;">⚠️</span>
        <h3 style="color: var(--color-primary-dark); margin-bottom: 8px;">Pesanan Tidak Ditemukan</h3>
        <p style="color: var(--color-text-muted); font-size: 0.95rem;">
          Nomor pesanan "<strong><?= e($orderCode) ?></strong>" tidak tercatat di database kami. Pastikan kode pesanan sudah benar.
        </p>
      </div>
    <?php elseif ($order): ?>
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 32px; box-shadow: var(--shadow-md);">
        <!-- Order Header -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; padding-bottom: 20px; border-bottom: 2px solid var(--color-cream-warm); margin-bottom: 24px;">
          <div>
            <div style="font-size: 0.85rem; color: var(--color-text-muted);">Kode Pesanan Resmi:</div>
            <h2 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--color-primary-dark);">
              #<?= e($order['order_code']) ?>
            </h2>
            <div style="font-size: 0.82rem; color: var(--color-text-muted);">
              Waktu Pesan: <?= date('d M Y, H:i', strtotime($order['created_at'])) ?> WIT
            </div>
          </div>

          <!-- Status Badge -->
          <div>
            <span class="badge-status badge-<?= e($order['order_status']) ?>" style="font-size: 0.95rem; padding: 8px 16px;">
              ● Status: <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
            </span>
          </div>
        </div>

        <!-- Order Items Breakdown -->
        <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 14px;">
          Rincian Barang yang Dipesan:
        </h4>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
          <?php foreach ($orderItems as $it): ?>
          <div style="display: flex; justify-content: space-between; padding: 10px 14px; background: var(--color-cream-bg); border-radius: var(--radius-sm); font-size: 0.92rem;">
            <span><strong><?= (int)$it['qty'] ?>x</strong> <?= e($it['product_name']) ?></span>
            <strong style="color: var(--color-primary);"><?= format_rupiah($it['subtotal']) ?></strong>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Breakdown Kalkulasi Total -->
        <div style="border-top: 1px dashed var(--color-cream-border); padding-top: 14px; margin-bottom: 24px; display: flex; flex-direction: column; gap: 8px; font-size: 0.95rem;">
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Total Kuantitas:</span>
            <strong><?= (int)$order['total_qty'] ?> bungkus</strong>
          </div>
          <?php 
          $calcSubtotal = 0;
          foreach ($orderItems as $it) { $calcSubtotal += (float)$it['subtotal']; }
          $discountVal = (float)($order['discount_amount'] ?? 0);
          $shippingVal = (float)($order['shipping_cost'] ?? 0);
          $hasDetails = ($discountVal > 0 || $shippingVal > 0 || !empty($order['voucher_code']) || !empty($order['shipping_zone']));
          ?>
          <?php if ($hasDetails): ?>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Subtotal Produk:</span>
            <strong><?= format_rupiah($calcSubtotal) ?></strong>
          </div>
          <?php if ($discountVal > 0 || !empty($order['voucher_code'])): ?>
          <div style="display: flex; justify-content: space-between; color: #15803D;">
            <span>Diskon Kupon <?= !empty($order['voucher_code']) ? '(' . e($order['voucher_code']) . ')' : '' ?>:</span>
            <strong>-<?= format_rupiah($discountVal) ?></strong>
          </div>
          <?php endif; ?>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Ongkos Kirim <?= !empty($order['shipping_zone']) ? '(' . e($order['shipping_zone']) . ')' : '' ?>:</span>
            <strong><?= ($shippingVal > 0) ? format_rupiah($shippingVal) : 'GRATIS (Rp 0)' ?></strong>
          </div>
          <?php endif; ?>
          <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 10px; border-top: 2px solid var(--color-cream-border); margin-top: 4px; font-size: 1.25rem; font-weight: 800; color: var(--color-primary-dark);">
            <span>Total Pembayaran:</span>
            <span style="color: var(--color-primary);"><?= format_rupiah($order['total_price']) ?></span>
          </div>
        </div>

        <!-- Customer & Delivery Info -->
        <div style="background: var(--color-cream-warm); border-radius: var(--radius-md); padding: 18px 20px; margin-bottom: 30px; font-size: 0.9rem;">
          <div style="margin-bottom: 8px;">
            <span style="color: var(--color-text-muted);">Nama Pembeli:</span>
            <strong><?= e($order['customer_name']) ?></strong> (WA: <?= e($order['customer_phone']) ?>)
          </div>
          <div style="margin-bottom: 8px;">
            <span style="color: var(--color-text-muted);">Metode Pembayaran:</span>
            <strong style="color: var(--color-primary);"><?= e(payment_method_label($order['payment_method'])) ?></strong>
          </div>
          <div style="margin-bottom: 8px;">
            <span style="color: var(--color-text-muted);">Alamat Pengiriman:</span>
            <strong><?= e($order['customer_address']) ?></strong>
          </div>
          <?php if (!empty($order['customer_notes'])): ?>
          <div style="margin-bottom: 8px;">
            <span style="color: var(--color-text-muted);">Catatan Khusus:</span>
            <em><?= e($order['customer_notes']) ?></em>
          </div>
          <?php endif; ?>

          <?php if (($order['payment_method'] ?? '') === 'cod'): ?>
          <div style="margin-top: 10px; padding: 10px 14px; background: #FEF3C7; border-radius: 6px; color: #92400E; font-size: 0.85rem;">
            🤝 <strong>Bayar di Tempat (COD):</strong> Siapkan uang tunai pas sebesar <strong><?= format_rupiah($order['total_price']) ?></strong> saat kurir KRISUKA mengantarkan pesanan ke rumah/kantor Anda.
          </div>
          <?php elseif (($order['payment_method'] ?? '') === 'qris'): ?>
          <div style="margin-top: 10px; padding: 10px 14px; background: #E0F2FE; border-radius: 6px; color: #0369A1; font-size: 0.85rem;">
            📱 <strong>Pembayaran QRIS:</strong> Admin akan mengirimkan barcode QRIS toko melalui WhatsApp untuk Anda scan memakai m-Banking / e-Wallet.
          </div>
          <?php elseif (($order['payment_method'] ?? '') === 'ewallet'): ?>
          <div style="margin-top: 10px; padding: 10px 14px; background: #F3E8FF; border-radius: 6px; color: #6B21A8; font-size: 0.85rem;">
            💳 <strong>Pembayaran E-Wallet:</strong> Admin akan menginformasikan nomor akun DANA / OVO / GoPay toko melalui WhatsApp.
          </div>
          <?php else: ?>
          <div style="margin-top: 10px; padding: 10px 14px; background: #DCFCE7; border-radius: 6px; color: #166534; font-size: 0.85rem;">
            🏦 <strong>Rekening Resmi KRISUKA:</strong> <?= e(get_setting('bank_info', 'BCA: 123-456-7890 a.n KRISUKA | BRI: 0987-01-023456-50-1 a.n KRISUKA')) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: flex-end;">
          <button type="button" class="btn btn-secondary" onclick="window.print();" style="padding: 10px 20px;">
            🖨️ Cetak Bukti Pesanan
          </button>

          <?php if (!empty($order['whatsapp_url'])): ?>
          <a href="<?= e($order['whatsapp_url']) ?>" class="btn-checkout" target="_blank" rel="noopener noreferrer" style="width: auto; padding: 10px 24px; text-decoration: none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            Kirim Pesanan ke WhatsApp Toko
          </a>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($openWa && !empty($order['whatsapp_url'])): ?>
      <script>
        // Otomatis buka WhatsApp toko setelah submit
        window.addEventListener('DOMContentLoaded', () => {
          setTimeout(() => {
            window.open(<?= json_encode($order['whatsapp_url']) ?>, '_blank');
          }, 800);
        });
      </script>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


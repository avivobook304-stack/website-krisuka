<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Detail & Kelola Pesanan';
$adminNav = 'orders';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ' . base_url('admin/orders.php'));
    exit;
}

$stmt = db()->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    header('Location: ' . base_url('admin/orders.php'));
    exit;
}

// Proses Update Status Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Validasi sesi gagal.');
    } else {
        $newStatus = sanitize($_POST['order_status'] ?? '');
        $validStatuses = ['menunggu_konfirmasi', 'dikonfirmasi', 'diproses', 'siap_dikirim', 'selesai', 'dibatalkan'];

        if (in_array($newStatus, $validStatuses)) {
            $now = date('Y-m-d H:i:s');
            $db = db();

            // Jika status diubah menjadi 'dibatalkan', kembalikan stok produk
            if ($newStatus === 'dibatalkan' && $order['order_status'] !== 'dibatalkan') {
                $items = $db->query("SELECT product_id, qty FROM order_items WHERE order_id = $orderId")->fetchAll();
                foreach ($items as $it) {
                    $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$it['qty'], $it['product_id']]);
                    $db->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, 'in', ?, ?, ?)")
                        ->execute([$it['product_id'], $it['qty'], "Pengembalian Stok Pembatalan Pesanan #{$order['order_code']}", $now]);
                }
            }

            $stmtUpdate = $db->prepare("UPDATE orders SET order_status = ?, updated_at = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $now, $orderId]);

            set_flash('success', "Status pesanan #{$order['order_code']} berhasil diperbarui menjadi: " . ucwords(str_replace('_', ' ', $newStatus)));
            header('Location: ' . base_url('admin/order-detail.php?id=' . $orderId));
            exit;
        }
    }
}

// Ambil item produk dalam pesanan
$stmtItems = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);

include __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <div>
    <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--admin-primary); margin-bottom: 4px;">
      Pesanan #<?= e($order['order_code']) ?>
    </h3>
    <small style="color: var(--admin-text-muted);">
      Dibuat pada: <?= date('d F Y, H:i:s', strtotime($order['created_at'])) ?> WIT
    </small>
  </div>

  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="<?= base_url('admin/orders.php') ?>" class="btn-action btn-action-edit">
      &larr; Kembali ke Daftar
    </a>
    <a href="<?= base_url('admin/shipping-label.php?id=' . $order['id']) ?>" target="_blank" class="btn-action" style="background: #0D9488; color: #FFFFFF;">
      📦 Cetak Label Paket (A6)
    </a>
    <a href="<?= base_url('admin/invoice.php?id=' . $order['id']) ?>" target="_blank" class="btn-action btn-action-primary">
      🖨️ Cetak Invoice
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; align-items: start;">
  <!-- Left Column: Item List & Pricing -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <h4 class="admin-card-title">Daftar Produk Pesanan</h4>
        <span>Total: <strong><?= (int)$order['total_qty'] ?> bungkus</strong></span>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Produk</th>
            <th>Harga Satuan</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><strong><?= e($it['product_name']) ?></strong></td>
            <td><?= format_rupiah($it['price']) ?></td>
            <td><?= (int)$it['qty'] ?> bungkus</td>
            <td><strong><?= format_rupiah($it['subtotal']) ?></strong></td>
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
            <th colspan="3" style="text-align: right; color: var(--admin-text-muted); font-weight: normal; padding-top: 14px;">Subtotal Produk:</th>
            <th style="padding-top: 14px;"><?= format_rupiah($calcSubtotal) ?></th>
          </tr>
          <?php if ($discountVal > 0 || !empty($order['voucher_code'])): ?>
          <tr>
            <th colspan="3" style="text-align: right; color: #15803D; font-weight: normal;">Diskon Kupon <?= !empty($order['voucher_code']) ? '(' . e($order['voucher_code']) . ')' : '' ?>:</th>
            <th style="color: #15803D;">-<?= format_rupiah($discountVal) ?></th>
          </tr>
          <?php endif; ?>
          <tr>
            <th colspan="3" style="text-align: right; color: var(--admin-text-muted); font-weight: normal;">Ongkos Kirim <?= !empty($order['shipping_zone']) ? '(' . e($order['shipping_zone']) . ')' : '' ?>:</th>
            <th><?= ($shippingVal > 0) ? format_rupiah($shippingVal) : 'GRATIS / Rp 0' ?></th>
          </tr>
          <?php endif; ?>
          <tr>
            <th colspan="3" style="text-align: right; font-size: 1.1rem; padding-top: 12px; border-top: 2px solid #E2E8F0;">Total Tagihan:</th>
            <th style="font-size: 1.25rem; color: var(--admin-primary); padding-top: 12px; border-top: 2px solid #E2E8F0;">
              <?= format_rupiah($order['total_price']) ?>
            </th>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Customer Information Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h4 class="admin-card-title">Informasi &amp; Alamat Pengiriman Pelanggan</h4>
      </div>

      <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem;">
        <div>
          <span style="color: var(--admin-text-muted); display: block;">Nama Lengkap:</span>
          <strong><?= e($order['customer_name']) ?></strong>
        </div>

        <div>
          <span style="color: var(--admin-text-muted); display: block;">Nomor WhatsApp:</span>
          <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px;">
            <strong style="font-size: 1rem; color: #15803D;"><?= e($order['customer_phone']) ?></strong>
            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode("Halo {$order['customer_name']}, terima kasih telah memesan keripik di KRISUKA (Kode: #{$order['order_code']}). Mengenai pesanan Anda...") ?>" 
               target="_blank" 
               class="btn-action btn-action-view" 
               style="padding: 4px 10px; font-size: 0.78rem;">
              Chat Pelanggan di WhatsApp &rarr;
            </a>
          </div>
        </div>

        <div>
          <span style="color: var(--admin-text-muted); display: block;">Metode Pembayaran Pilihan:</span>
          <strong style="font-size: 0.95rem; color: var(--admin-primary); display: inline-block; margin-top: 2px;">
            <?= e(payment_method_label($order['payment_method'])) ?>
          </strong>
        </div>

        <div>
          <span style="color: var(--admin-text-muted); display: block;">Alamat Lengkap Pengiriman:</span>
          <div style="background: var(--admin-bg); padding: 12px; border-radius: 8px; margin-top: 4px; line-height: 1.6;">
            <?= nl2br(e($order['customer_address'])) ?>
            
            <?php if (preg_match('/https:\/\/maps\.google\.com\/\?q=[^ \n\r\t]+/', $order['customer_address'], $gpsMatch)): ?>
              <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #CBD5E1;">
                <a href="<?= e($gpsMatch[0]) ?>" target="_blank" class="btn-action" style="background: #0284C7; color: #FFFFFF; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px;">
                  🗺️ Buka Titik Lokasi GPS di Google Maps &rarr;
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($order['customer_notes'])): ?>
        <div>
          <span style="color: var(--admin-text-muted); display: block;">Catatan Khusus Pembeli:</span>
          <div style="background: #FEF3C7; padding: 10px 14px; border-radius: 8px; color: #92400E; margin-top: 4px;">
            <em><?= e($order['customer_notes']) ?></em>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Status Management -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <h4 class="admin-card-title">Perbarui Status Pesanan</h4>
      </div>

      <div style="margin-bottom: 20px;">
        <span style="font-size: 0.85rem; color: var(--admin-text-muted); display: block; margin-bottom: 6px;">Status Saat Ini:</span>
        <span class="badge-status badge-<?= e($order['order_status']) ?>" style="font-size: 1rem; padding: 6px 14px;">
          ● <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
        </span>
      </div>

      <form action="<?= base_url('admin/order-detail.php?id=' . $order['id']) ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label" for="orderStatusSelect">Ubah Menjadi Status Baru:</label>
          <select id="orderStatusSelect" name="order_status" class="form-control" style="font-weight: 700;">
            <option value="menunggu_konfirmasi" <?= ($order['order_status'] === 'menunggu_konfirmasi') ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
            <option value="dikonfirmasi" <?= ($order['order_status'] === 'dikonfirmasi') ? 'selected' : '' ?>>Dikonfirmasi (Pembayaran Valid)</option>
            <option value="diproses" <?= ($order['order_status'] === 'diproses') ? 'selected' : '' ?>>Diproses (Sedang Digoreng / Dikemas)</option>
            <option value="siap_dikirim" <?= ($order['order_status'] === 'siap_dikirim') ? 'selected' : '' ?>>Siap Dikirim</option>
            <option value="selesai" <?= ($order['order_status'] === 'selesai') ? 'selected' : '' ?>>Selesai (Barang Diterima)</option>
            <option value="dibatalkan" <?= ($order['order_status'] === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan (Stok Dikembalikan)</option>
          </select>
        </div>

        <div style="background: #E0F2FE; color: #075985; font-size: 0.82rem; padding: 12px; border-radius: 8px; margin-bottom: 20px; line-height: 1.5;">
          ℹ️ <strong>Catatan Penting:</strong> Hanya ubah status menjadi <strong>Dikonfirmasi</strong> apabila pembayaran transfer bank dari pelanggan telah dicek dan valid di mutasi rekening.
        </div>

        <button type="submit" class="btn-action btn-action-primary" style="width: 100%; padding: 12px; justify-content: center; font-size: 0.95rem;">
          Simpan Status Baru
        </button>
      </form>
    </div>

    <!-- Quick Message Helper -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h4 class="admin-card-title">Pesan Konfirmasi WA Otomatis</h4>
      </div>
      <p style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 14px;">
        Format pesan pesanan yang terkirim dari website saat pelanggan melakukan checkout:
      </p>
      <div style="background: var(--admin-bg); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 0.78rem; max-height: 180px; overflow-y: auto; white-space: pre-wrap; color: #334155;">
        <?= e(build_whatsapp_order_message($order, $items)) ?>
      </div>
    </div>
  </div>
</div>

<?php if (isset($_GET['wa']) && $_GET['wa'] == 1 && !empty($order['whatsapp_url'])): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    window.open(<?= json_encode($order['whatsapp_url']) ?>, '_blank');
  }, 600);
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>


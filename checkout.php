<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'cart';
$pageTitle = 'Checkout Pesanan | KRISUKA Fakfak';

$cart = cart_get();
$totals = cart_totals();

if (empty($cart)) {
    set_flash('warning', 'Keranjang belanja Anda masih kosong. Silakan pilih produk terlebih dahulu.');
    header('Location: ' . base_url('products.php'));
    exit;
}

$errors = [];

// Proses Submit Form Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi keamanan gagal. Silakan muat ulang halaman.';
    }

    $buyerName = sanitize($_POST['buyer_name'] ?? '');
    $buyerPhone = sanitize($_POST['buyer_phone'] ?? '');
    $buyerAddress = sanitize($_POST['buyer_address'] ?? '');
    $buyerNotes = sanitize($_POST['buyer_notes'] ?? '');
    $shippingZoneRaw = sanitize($_POST['shipping_zone'] ?? 'Dalam Kota Fakfak (Kurir Lokal)|0');
    $voucherCodeInput = strtoupper(trim(sanitize($_POST['voucher_code'] ?? '')));
    $paymentMethodInput = sanitize($_POST['payment_method'] ?? 'bank_transfer');

    // Validasi Metode Pembayaran
    $validPayments = ['bank_transfer', 'qris', 'cod', 'ewallet'];
    $paymentMethod = in_array($paymentMethodInput, $validPayments) ? $paymentMethodInput : 'bank_transfer';

    // Parse shipping zone & ongkos kirim
    $shippingParts = explode('|', $shippingZoneRaw);
    $shippingZone = trim($shippingParts[0] ?? 'Dalam Kota Fakfak (Kurir Lokal)');
    $shippingCost = isset($shippingParts[1]) ? max(0, (float)$shippingParts[1]) : 0.0;

    $subtotal = (float)$totals['total_price'];
    $discountAmount = 0.0;
    $appliedVoucherId = null;
    $appliedVoucherCode = null;

    if (empty($buyerName)) {
        $errors[] = 'Nama lengkap pembeli wajib diisi.';
    }
    if (empty($buyerPhone)) {
        $errors[] = 'Nomor WhatsApp aktif wajib diisi.';
    }
    if (empty($buyerAddress)) {
        $errors[] = 'Alamat lengkap pengiriman wajib diisi.';
    }

    // Validasi voucher jika diinput
    if (!empty($voucherCodeInput)) {
        $vStmt = db()->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1");
        $vStmt->execute([$voucherCodeInput]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);

        if ($vRow) {
            $today = date('Y-m-d');
            if ($vRow['valid_until'] < $today) {
                $errors[] = "Kupon '{$voucherCodeInput}' sudah kedaluwarsa.";
            } elseif ($vRow['used_count'] >= $vRow['usage_limit']) {
                $errors[] = "Kuota penggunaan kupon '{$voucherCodeInput}' telah habis.";
            } elseif ($subtotal < (float)$vRow['min_spend']) {
                $errors[] = "Minimal belanja untuk menggunakan kupon '{$voucherCodeInput}' adalah " . format_rupiah($vRow['min_spend']) . ".";
            } else {
                $appliedVoucherId = (int)$vRow['id'];
                $appliedVoucherCode = $vRow['code'];

                if ($vRow['discount_type'] === 'percent') {
                    $discountAmount = ($subtotal * (float)$vRow['discount_value']) / 100;
                    if ((float)$vRow['max_discount'] > 0 && $discountAmount > (float)$vRow['max_discount']) {
                        $discountAmount = (float)$vRow['max_discount'];
                    }
                } else {
                    $discountAmount = min($subtotal, (float)$vRow['discount_value']);
                }
            }
        } else {
            $errors[] = "Kode kupon '{$voucherCodeInput}' tidak valid atau belum terdaftar.";
        }
    }

    $grandTotal = max(0, $subtotal - $discountAmount + $shippingCost);

    if (empty($errors)) {
        $db = db();
        try {
            $db->beginTransaction();

            $orderCode = generate_order_code();
            $storeWa = get_setting('whatsapp_number', '6282274761719');

            // Susun data order
            $orderData = [
                'order_code' => $orderCode,
                'customer_name' => $buyerName,
                'customer_phone' => $buyerPhone,
                'customer_address' => $buyerAddress,
                'customer_notes' => $buyerNotes,
                'total_qty' => $totals['total_qty'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'voucher_code' => $appliedVoucherCode,
                'shipping_cost' => $shippingCost,
                'shipping_zone' => $shippingZone,
                'payment_method' => $paymentMethod,
                'total_price' => $grandTotal
            ];

            // Susun array item
            $itemsForWa = [];
            foreach ($cart as $item) {
                $itemsForWa[] = [
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty']
                ];
            }

            // Generate WhatsApp message
            $waMessage = build_whatsapp_order_message($orderData, $itemsForWa);
            $cleanWaPhone = preg_replace('/[^0-9]/', '', $storeWa);
            $waUrl = "https://wa.me/{$cleanWaPhone}?text=" . urlencode($waMessage);

            // 1. Insert ke tabel orders
            $stmtOrder = $db->prepare("INSERT INTO orders 
                (order_code, customer_name, customer_phone, customer_address, customer_notes, total_qty, total_price, voucher_code, discount_amount, shipping_cost, shipping_zone, payment_method, order_status, whatsapp_url, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu_konfirmasi', ?, ?, ?)");
            
            $now = date('Y-m-d H:i:s');
            $stmtOrder->execute([
                $orderCode, $buyerName, $buyerPhone, $buyerAddress, $buyerNotes, 
                $totals['total_qty'], $grandTotal, $appliedVoucherCode, $discountAmount, $shippingCost, $shippingZone, $paymentMethod, $waUrl, $now, $now
            ]);
            $orderId = (int)$db->lastInsertId();

            // 2. Insert ke tabel order_items & kurangi stok produk
            $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtReduceStock = $db->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?");
            $stmtLogStock = $db->prepare("INSERT INTO stock_logs (product_id, type, quantity, note, created_at) VALUES (?, 'out', ?, ?, ?)");

            foreach ($cart as $it) {
                $itSubtotal = $it['price'] * $it['qty'];
                $stmtItem->execute([
                    $orderId, $it['id'], $it['name'], $it['price'], $it['qty'], $itSubtotal
                ]);

                // Kurangi stok & catat log
                $stmtReduceStock->execute([$it['qty'], $it['id']]);
                $stmtLogStock->execute([$it['id'], $it['qty'], "Pesanan " . $orderCode, $now]);
            }

            // 3. Update penggunaan voucher jika ada
            if ($appliedVoucherId) {
                $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?")->execute([$appliedVoucherId]);
            }

            $db->commit();

            // Kosongkan keranjang
            cart_clear();

            // Simpan flash & arahkan pelanggan ke halaman Nota & Status Pesanan Pelanggan (Bukan Admin)
            set_flash('success', "Pesanan #{$orderCode} berhasil disimpan! Rincian pesanan Anda sedang diarahkan ke WhatsApp toko.");
            header("Location: " . base_url("order-status.php?order_code={$orderCode}&wa=1"));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Gagal menyimpan pesanan ke database: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 30px 0; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <h1 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-primary-dark); margin-bottom: 6px;">
      Formulir Checkout &amp; Pemesanan
    </h1>
    <p style="color: var(--color-text-muted); font-size: 0.95rem;">
      Lengkapi data pengiriman di bawah ini. Pesanan Anda akan tersimpan di database dan otomatis terhubung ke WhatsApp resmi KRISUKA.
    </p>
  </div>
</div>

<section style="padding: 50px 0 80px;">
  <div class="container">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 24px;">
        <strong>Mohon perbaiki kesalahan berikut:</strong>
        <ul style="margin: 6px 0 0 20px;">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="checkout-layout-grid">
      <!-- Customer Information Form -->
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 30px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--color-cream-border);">
          1. Data Pemesan &amp; Alamat Pengiriman
        </h3>

        <form action="<?= base_url('checkout.php') ?>" method="POST" id="checkoutForm">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label" for="buyerName">Nama Lengkap Pembeli <span class="required">*</span></label>
            <input type="text" id="buyerName" name="buyer_name" class="form-control" placeholder="Contoh: Rahmat Hidayat" value="<?= e($_POST['buyer_name'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="buyerPhone">Nomor WhatsApp Aktif <span class="required">*</span></label>
            <input type="tel" id="buyerPhone" name="buyer_phone" class="form-control" placeholder="Contoh: 081234567890" value="<?= e($_POST['buyer_phone'] ?? '') ?>" required>
            <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;">
              Digunakan untuk konfirmasi pesanan, update nomor resi pengiriman, dan info rekening pembayaran.
            </div>
          </div>

          <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <label class="form-label" for="buyerAddress" style="margin-bottom: 0;">
                Alamat Lengkap Pengiriman <span class="required">*</span>
              </label>
              <button type="button" id="btnGetLocation" class="btn-location-gps" onclick="getLocationCurrent();" title="Ambil titik koordinat & alamat GPS saat ini">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                </svg>
                <span>📍 Gunakan Lokasi Terkini</span>
              </button>
            </div>
            <textarea id="buyerAddress" name="buyer_address" class="form-control" placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, kelurahan, distrik/kecamatan, kota/kabupaten... atau klik 'Gunakan Lokasi Terkini'" required><?= e($_POST['buyer_address'] ?? '') ?></textarea>
            <div id="locationStatus" style="font-size: 0.8rem; margin-top: 5px; display: none;"></div>
          </div>

          <!-- Pilihan Wilayah Pengiriman -->
          <div class="form-group">
            <label class="form-label" for="shippingZone">Wilayah Pengiriman &amp; Ongkos Kirim <span class="required">*</span></label>
            <select id="shippingZone" name="shipping_zone" class="form-control" onchange="updateCheckoutCalc();" required>
              <option value="Dalam Kota Fakfak (Kurir Lokal)|0" <?= (($_POST['shipping_zone'] ?? '') === 'Dalam Kota Fakfak (Kurir Lokal)|0') ? 'selected' : '' ?>>📍 Dalam Kota Fakfak (Kurir Lokal) — GRATIS / Rp 0</option>
              <option value="Luar Kota Fakfak (Fakfak Barat/Timur/Kokas)|15000" <?= (($_POST['shipping_zone'] ?? '') === 'Luar Kota Fakfak (Fakfak Barat/Timur/Kokas)|15000') ? 'selected' : '' ?>>🚚 Luar Kota Fakfak (Fakfak Barat/Timur/Kokas) — Rp 15.000</option>
              <option value="Papua & Papua Barat (Sorong, Manokwari, Jayapura, dll)|25000" <?= (($_POST['shipping_zone'] ?? '') === 'Papua & Papua Barat (Sorong, Manokwari, Jayapura, dll)|25000') ? 'selected' : '' ?>>✈️ Papua &amp; Papua Barat (Sorong, Manokwari, dll) — Rp 25.000</option>
              <option value="Luar Pulau Papua (JNE / J&T / Pos Indonesia)|40000" <?= (($_POST['shipping_zone'] ?? '') === 'Luar Pulau Papua (JNE / J&T / Pos Indonesia)|40000') ? 'selected' : '' ?>>📦 Luar Pulau Papua (Antar Pulau JNE/J&T/Pos) — Rp 40.000</option>
            </select>
            <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;">
              Ongkos kirim otomatis ditambahkan ke total pesanan sesuai destinasi Anda.
            </div>
          </div>

          <!-- Kupon Diskon Voucher -->
          <div class="form-group">
            <label class="form-label" for="voucherCode">Punya Kode Kupon Diskon? (Opsional)</label>
            <div style="display: flex; gap: 8px;">
              <input type="text" id="voucherCode" name="voucher_code" class="form-control" placeholder="Contoh: KRISUKAFK / HEMAT10" value="<?= e($_POST['voucher_code'] ?? '') ?>" style="text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">
              <button type="button" id="btnApplyVoucher" class="btn btn-primary" style="white-space: nowrap; padding: 0 16px; font-size: 0.88rem;" onclick="applyVoucherCheckout();">
                Terapkan
              </button>
            </div>
            <div id="voucherFeedback" style="font-size: 0.82rem; margin-top: 6px; display: none;"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="buyerNotes">Catatan Tambahan (Opsional)</label>
            <input type="text" id="buyerNotes" name="buyer_notes" class="form-control" placeholder="Contoh: Tolong packing kardus rapi untuk oleh-oleh" value="<?= e($_POST['buyer_notes'] ?? '') ?>">
          </div>

          <!-- Pilihan Metode Pembayaran -->
          <div class="form-group" style="margin-top: 24px;">
            <label class="form-label" style="font-weight: 700; margin-bottom: 12px; display: block;">
              Pilih Metode Pembayaran <span class="required">*</span>
            </label>

            <div class="payment-methods-grid">
              <!-- 1. Transfer Bank -->
              <label class="payment-card <?= (($_POST['payment_method'] ?? 'bank_transfer') === 'bank_transfer') ? 'selected' : '' ?>" id="payCard-bank_transfer" onclick="selectPaymentMethod('bank_transfer');">
                <input type="radio" name="payment_method" value="bank_transfer" <?= (($_POST['payment_method'] ?? 'bank_transfer') === 'bank_transfer') ? 'checked' : '' ?> style="display: none;">
                <div class="payment-card-icon">🏦</div>
                <div class="payment-card-body">
                  <strong class="payment-title">Transfer Bank</strong>
                  <span class="payment-sub">BCA, BRI, Mandiri</span>
                </div>
                <div class="payment-check-circle">✓</div>
              </label>

              <!-- 2. QRIS -->
              <label class="payment-card <?= (($_POST['payment_method'] ?? '') === 'qris') ? 'selected' : '' ?>" id="payCard-qris" onclick="selectPaymentMethod('qris');">
                <input type="radio" name="payment_method" value="qris" <?= (($_POST['payment_method'] ?? '') === 'qris') ? 'checked' : '' ?> style="display: none;">
                <div class="payment-card-icon">📱</div>
                <div class="payment-card-body">
                  <strong class="payment-title">QRIS</strong>
                  <span class="payment-sub">Semua Bank &amp; E-Wallet</span>
                </div>
                <div class="payment-check-circle">✓</div>
              </label>

              <!-- 3. Bayar di Tempat (COD) -->
              <label class="payment-card <?= (($_POST['payment_method'] ?? '') === 'cod') ? 'selected' : '' ?>" id="payCard-cod" onclick="selectPaymentMethod('cod');">
                <input type="radio" name="payment_method" value="cod" <?= (($_POST['payment_method'] ?? '') === 'cod') ? 'checked' : '' ?> style="display: none;">
                <div class="payment-card-icon">🤝</div>
                <div class="payment-card-body">
                  <strong class="payment-title">Bayar di Tempat (COD)</strong>
                  <span class="payment-sub">Khusus Kota Fakfak</span>
                </div>
                <div class="payment-check-circle">✓</div>
              </label>

              <!-- 4. E-Wallet -->
              <label class="payment-card <?= (($_POST['payment_method'] ?? '') === 'ewallet') ? 'selected' : '' ?>" id="payCard-ewallet" onclick="selectPaymentMethod('ewallet');">
                <input type="radio" name="payment_method" value="ewallet" <?= (($_POST['payment_method'] ?? '') === 'ewallet') ? 'checked' : '' ?> style="display: none;">
                <div class="payment-card-icon">💳</div>
                <div class="payment-card-body">
                  <strong class="payment-title">E-Wallet</strong>
                  <span class="payment-sub">DANA, OVO, GoPay, ShopeePay</span>
                </div>
                <div class="payment-check-circle">✓</div>
              </label>
            </div>

            <!-- Petunjuk Dinamis Metode Pembayaran -->
            <div id="paymentInstructions" style="background: var(--color-cream-bg); border: 1px solid var(--color-cream-border); border-radius: var(--radius-md); padding: 14px 18px; margin-top: 14px; margin-bottom: 24px; font-size: 0.86rem; line-height: 1.6; color: var(--color-text-muted);">
            </div>
          </div>

          <button type="submit" class="btn-checkout" style="cursor: pointer;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            Konfirmasi Pesanan ke WhatsApp Toko
          </button>
        </form>
      </div>

      <!-- Order Summary Card -->
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 24px; box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
        <h3 style="font-family: var(--font-heading); font-size: 1.25rem; color: var(--color-primary-dark); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--color-cream-border);">
          2. Rincian Pesanan
        </h3>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
          <?php foreach ($cart as $it): ?>
          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 0.92rem;">
            <div>
              <strong><?= (int)$it['qty'] ?>x</strong> <?= e($it['name']) ?>
              <div style="font-size: 0.78rem; color: var(--color-text-muted);">@ <?= format_rupiah($it['price']) ?></div>
            </div>
            <strong style="color: var(--color-primary-dark);">
              <?= format_rupiah($it['price'] * $it['qty']) ?>
            </strong>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="border-top: 1px dashed var(--color-cream-border); padding-top: 14px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 10px; font-size: 0.92rem;">
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Total Kuantitas:</span>
            <strong><?= (int)$totals['total_qty'] ?> bungkus</strong>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Subtotal Produk:</span>
            <strong id="displaySubtotal" style="color: var(--color-text-dark);"><?= format_rupiah($totals['total_price']) ?></strong>
          </div>
          <div id="rowDiscount" style="display: none; justify-content: space-between; color: #15803D;">
            <span>Diskon Kupon (<span id="displayVoucherCode"></span>):</span>
            <strong id="displayDiscount">-Rp 0</strong>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--color-text-muted);">
            <span>Ongkos Kirim:</span>
            <strong id="displayShipping" style="color: var(--color-text-dark);">GRATIS (Rp 0)</strong>
          </div>
          <div style="border-top: 2px solid var(--color-cream-border); padding-top: 12px; margin-top: 4px; display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--color-primary);">
            <span>Total Pembayaran:</span>
            <span id="displayGrandTotal"><?= format_rupiah($totals['total_price']) ?></span>
          </div>
        </div>

        <div style="font-size: 0.8rem; color: var(--color-text-muted); line-height: 1.5; background: var(--color-cream-bg); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--color-cream-border);">
          💡 <strong>Nomor WhatsApp Toko:</strong> Pesanan ini akan langsung terhubung ke nomor WhatsApp resmi KRISUKA yang terdaftar di sistem.
        </div>
      </div>
    </div>
  </div>
</section>

<script>
const baseSubtotal = <?= (float)$totals['total_price'] ?>;
let appliedDiscount = 0;
let activeVoucherCode = '';

function formatRupiahJs(number) {
  return 'Rp ' + Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function updateCheckoutCalc() {
  const zoneSelect = document.getElementById('shippingZone');
  let shippingCost = 0;
  if (zoneSelect && zoneSelect.value) {
    const parts = zoneSelect.value.split('|');
    shippingCost = parseFloat(parts[1]) || 0;
  }

  const shippingDisplay = document.getElementById('displayShipping');
  if (shippingDisplay) {
    shippingDisplay.textContent = shippingCost > 0 ? formatRupiahJs(shippingCost) : 'GRATIS (Rp 0)';
  }

  const grandTotal = Math.max(0, baseSubtotal - appliedDiscount + shippingCost);
  const grandTotalEl = document.getElementById('displayGrandTotal');
  if (grandTotalEl) {
    grandTotalEl.textContent = formatRupiahJs(grandTotal);
  }
}

async function applyVoucherCheckout() {
  const input = document.getElementById('voucherCode');
  const feedback = document.getElementById('voucherFeedback');
  const btn = document.getElementById('btnApplyVoucher');
  const code = (input.value || '').trim().toUpperCase();

  if (!code) {
    feedback.style.display = 'block';
    feedback.style.color = '#DC2626';
    feedback.textContent = 'Silakan masukkan kode kupon terlebih dahulu.';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Memeriksa...';

  try {
    const res = await fetch('<?= base_url('cart-action.php?action=check_voucher') ?>&code=' + encodeURIComponent(code));
    const data = await res.json();

    feedback.style.display = 'block';
    if (data.success) {
      appliedDiscount = parseFloat(data.discount_amount) || 0;
      activeVoucherCode = data.code;
      feedback.style.color = '#15803D';
      feedback.textContent = '✓ ' + data.message;

      document.getElementById('rowDiscount').style.display = 'flex';
      document.getElementById('displayVoucherCode').textContent = activeVoucherCode;
      document.getElementById('displayDiscount').textContent = '-' + data.formatted_discount;
      updateCheckoutCalc();
    } else {
      appliedDiscount = 0;
      activeVoucherCode = '';
      feedback.style.color = '#DC2626';
      feedback.textContent = '✕ ' + data.message;
      document.getElementById('rowDiscount').style.display = 'none';
      updateCheckoutCalc();
    }
  } catch (e) {
    feedback.style.display = 'block';
    feedback.style.color = '#DC2626';
    feedback.textContent = 'Gagal memeriksa kupon: ' + e.message;
  } finally {
    btn.disabled = false;
    btn.textContent = 'Terapkan';
  }
}

const paymentDetails = {
  bank_transfer: '🏦 <strong>Transfer Bank Manual:</strong> Rekening resmi KRISUKA (BCA / BRI) akan diberikan oleh admin saat konfirmasi ketersediaan stok via WhatsApp. Pesanan dikirim setelah bukti transfer diverifikasi.',
  qris: '📱 <strong>QRIS Instan:</strong> Barcode QRIS resmi toko akan dikirimkan langsung ke chat WhatsApp Anda. Dapat discan memakai BCA Mobile, BRImo, Mandiri Livin\', GoPay, OVO, DANA, atau ShopeePay.',
  cod: '🤝 <strong>Bayar di Tempat (COD):</strong> Anda membayar tunai langsung kepada kurir KRISUKA saat paket keripik tiba di alamat Anda. <em>*Layanan COD khusus area pengiriman dalam Kota Fakfak.</em>',
  ewallet: '💳 <strong>E-Wallet:</strong> Transfer saldo langsung ke nomor akun DANA, OVO, atau GoPay resmi KRISUKA setelah konfirmasi chat WhatsApp tanpa biaya transfer antar-bank.'
};

function selectPaymentMethod(method) {
  document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
  const card = document.getElementById('payCard-' + method);
  if (card) {
    card.classList.add('selected');
    const radio = card.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
  }
  const infoEl = document.getElementById('paymentInstructions');
  if (infoEl && paymentDetails[method]) {
    infoEl.innerHTML = paymentDetails[method];
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateCheckoutCalc();
  
  // Inisialisasi metode pembayaran awal
  const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
  const initialMethod = checkedRadio ? checkedRadio.value : 'bank_transfer';
  selectPaymentMethod(initialMethod);

  const initialVoucher = document.getElementById('voucherCode');
  if (initialVoucher && initialVoucher.value.trim() !== '') {
    applyVoucherCheckout();
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

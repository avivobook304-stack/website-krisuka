<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'cart';
$pageTitle = 'Keranjang Belanja | KRISUKA Fakfak';

$cart = cart_get();
$totals = cart_totals();

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 30px 0; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <h1 style="font-family: var(--font-heading); font-size: 2rem; color: var(--color-primary-dark); margin-bottom: 6px;">
      Keranjang Belanja Anda
    </h1>
    <p style="color: var(--color-text-muted); font-size: 0.95rem;">
      Periksa kembali daftar varian keripik KRISUKA sebelum melanjutkan ke pemesanan via WhatsApp.
    </p>
  </div>
</div>

<section style="padding: 50px 0 80px;">
  <div class="container">
    <?php if (empty($cart)): ?>
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px dashed var(--color-cream-border); padding: 80px 20px; text-align: center; max-width: 600px; margin: 0 auto;">
        <span style="font-size: 4rem; display: block; margin-bottom: 16px;">🛒</span>
        <h2 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--color-primary-dark); margin-bottom: 8px;">
          Keranjang Belanja Masih Kosong
        </h2>
        <p style="color: var(--color-text-muted); font-size: 1rem; margin-bottom: 30px;">
          Anda belum memilih varian keripik pisang, sukun, atau keladi khas Fakfak. Yuk temukan varian favoritmu!
        </p>
        <a href="<?= base_url('products.php') ?>" class="btn btn-primary">
          Mulai Belanja Sekarang &rarr;
        </a>
      </div>
    <?php else: ?>
      <div class="cart-layout-grid">
        <!-- Cart Items Table / List -->
        <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 24px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--color-cream-border);">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--color-primary-dark);">
              Daftar Barang (<?= count($cart) ?> varian)
            </h3>
            <form action="<?= base_url('cart-action.php') ?>" method="POST" onsubmit="return confirm('Kosongkan seluruh isi keranjang?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear">
              <button type="submit" style="color: var(--color-danger); font-size: 0.85rem; font-weight: 600;">
                Kosongkan Keranjang
              </button>
            </form>
          </div>

          <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($cart as $item): ?>
            <?php $subtotal = $item['price'] * $item['qty']; ?>
            <div class="cart-item-card" style="display: flex; align-items: center; gap: 16px; padding-bottom: 18px; border-bottom: 1px dashed var(--color-cream-border);">
              <img 
                src="<?= e(product_image_url($item['image'])) ?>" 
                alt="<?= e($item['name']) ?>" 
                style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--color-cream-border); flex-shrink: 0;"
              >

              <div style="flex-grow: 1;">
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 4px;">
                  <?= e($item['name']) ?>
                </h4>
                <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 10px;">
                  Harga: <?= format_rupiah($item['price']) ?> &bull; <?= e($item['netto']) ?>
                </div>

                <div class="cart-item-actions" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                  <!-- Qty update form -->
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <form action="<?= base_url('cart-action.php') ?>" method="POST" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="quantity" value="<?= $item['qty'] - 1 ?>">
                      <button type="submit" class="qty-btn" style="width: 30px; height: 30px;">-</button>
                    </form>

                    <span style="font-size: 0.95rem; font-weight: 800; min-width: 30px; text-align: center;">
                      <?= (int)$item['qty'] ?>
                    </span>

                    <form action="<?= base_url('cart-action.php') ?>" method="POST" style="display:inline;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="quantity" value="<?= $item['qty'] + 1 ?>">
                      <button type="submit" class="qty-btn" style="width: 30px; height: 30px;">+</button>
                    </form>
                  </div>

                  <!-- Subtotal -->
                  <div style="font-size: 1.05rem; font-weight: 800; color: var(--color-primary);">
                    <?= format_rupiah($subtotal) ?>
                  </div>

                  <!-- Delete -->
                  <form action="<?= base_url('cart-action.php') ?>" method="POST" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                    <button type="submit" style="color: var(--color-danger); font-size: 0.82rem; padding: 6px; cursor: pointer; display: flex; align-items: center; gap: 3px;" title="Hapus produk">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                      <span>Hapus</span>
                    </button>
                  </form>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 20px;">
            <a href="<?= base_url('products.php') ?>" style="color: var(--color-primary); font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
              &larr; Lanjut Pilih Varian Lainnya
            </a>
          </div>
        </div>

        <!-- Order Summary & Checkout Action -->
        <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 24px; box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
          <h3 style="font-family: var(--font-heading); font-size: 1.25rem; color: var(--color-primary-dark); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--color-cream-border);">
            Ringkasan Belanja
          </h3>

          <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--color-text-muted); margin-bottom: 12px;">
            <span>Total Kuantitas:</span>
            <strong style="color: var(--color-text-dark);"><?= (int)$totals['total_qty'] ?> bungkus</strong>
          </div>

          <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--color-text-muted); margin-bottom: 16px;">
            <span>Pengiriman:</span>
            <span style="font-weight: 600; color: var(--color-primary-dark);">Dikonfirmasi via WA</span>
          </div>

          <div style="display: flex; justify-content: space-between; font-size: 1.35rem; font-weight: 800; color: var(--color-primary-dark); padding-top: 14px; border-top: 1px dashed var(--color-cream-border); margin-bottom: 24px;">
            <span>Total Tagihan:</span>
            <span style="color: var(--color-primary);"><?= format_rupiah($totals['total_price']) ?></span>
          </div>

          <a href="<?= base_url('checkout.php') ?>" class="btn-checkout" style="text-align: center; justify-content: center; text-decoration: none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            Lanjut ke Form Checkout &rarr;
          </a>

          <div style="margin-top: 16px; font-size: 0.8rem; color: var(--color-text-muted); text-align: center; line-height: 1.4;">
            🛡️ Pesanan aman &amp; tersimpan otomatis di database sistem KRISUKA.
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


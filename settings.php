<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Pengaturan Toko';
$adminNav = 'settings';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $fields = [
            'store_name',
            'store_abbreviation',
            'store_tagline',
            'store_origin',
            'whatsapp_number',
            'instagram_handle',
            'instagram_url',
            'store_address',
            'operational_hours',
            'shipping_info',
            'bank_info'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $val = trim($_POST[$field]);
                if ($field === 'whatsapp_number') {
                    // Bersihkan karakter selain angka
                    $val = preg_replace('/[^0-9]/', '', $val);
                }
                update_setting($field, $val);
            }
        }

        set_flash('success', 'Pengaturan toko berhasil diperbarui!');
        header('Location: ' . base_url('admin/settings.php'));
        exit;
    }
}

// Ambil nilai pengaturan terkini
$settings = [
    'store_name' => get_setting('store_name', 'KRISUKA'),
    'store_abbreviation' => get_setting('store_abbreviation', 'Keripik Sukun dan Keladi'),
    'store_tagline' => get_setting('store_tagline', 'Renyah, Lezat, Cita Rasa Lokal.'),
    'store_origin' => get_setting('store_origin', 'Fakfak, Papua Barat'),
    'whatsapp_number' => get_setting('whatsapp_number', '6282274761719'),
    'instagram_handle' => get_setting('instagram_handle', '@krisuka.fakfak'),
    'instagram_url' => get_setting('instagram_url', 'https://www.instagram.com/krisuka.fakfak?utm_source=ig_web_button_share_sheet&cplk=ZDNlZDc0MzIxNw=='),
    'store_address' => get_setting('store_address', 'Fakfak, Papua Barat, Indonesia'),
    'operational_hours' => get_setting('operational_hours', 'Senin - Sabtu: 08.00 - 20.00 WIT'),
    'shipping_info' => get_setting('shipping_info', 'Pengiriman dari Fakfak, Papua Barat ke seluruh Nusantara.'),
    'bank_info' => get_setting('bank_info', 'BCA: 123-456-7890 a.n KRISUKA | BRI: 0987-01-023456-50-1 a.n KRISUKA')
];

include __DIR__ . '/includes/header.php';
?>

<div class="admin-card" style="max-width: 820px; margin: 0 auto;">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Pengaturan Identitas &amp; Kontak Toko</h3>
      <small style="color: var(--admin-text-muted);">Ubah nomor WhatsApp tujuan checkout, alamat toko, dan rekening transfer di sini.</small>
    </div>
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

  <form action="<?= base_url('admin/settings.php') ?>" method="POST">
    <?= csrf_field() ?>

    <h4 style="color: var(--admin-primary); margin-bottom: 14px; padding-bottom: 6px; border-bottom: 1px solid var(--admin-border);">
      1. Identitas Brand &amp; Slogan
    </h4>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="storeName">Nama Brand Toko <span style="color:red;">*</span></label>
        <input type="text" id="storeName" name="store_name" class="form-control" value="<?= e($settings['store_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="storeAbbr">Kepanjangan Nama Brand</label>
        <input type="text" id="storeAbbr" name="store_abbreviation" class="form-control" value="<?= e($settings['store_abbreviation']) ?>" placeholder="Contoh: Keripik Sukun dan Keladi">
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="storeTagline">Slogan Brand</label>
        <input type="text" id="storeTagline" name="store_tagline" class="form-control" value="<?= e($settings['store_tagline']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="storeOrigin">Asal Daerah Usaha</label>
        <input type="text" id="storeOrigin" name="store_origin" class="form-control" value="<?= e($settings['store_origin']) ?>">
      </div>
    </div>

    <h4 style="color: var(--admin-primary); margin: 24px 0 14px; padding-bottom: 6px; border-bottom: 1px solid var(--admin-border);">
      2. Kontak WhatsApp &amp; Sosial Media
    </h4>

    <div class="form-group">
      <label class="form-label" for="storeWa" style="color: #15803D;">
        Nomor WhatsApp Toko (Tujuan Checkout) <span style="color:red;">*</span>
      </label>
      <input type="text" id="storeWa" name="whatsapp_number" class="form-control" value="<?= e($settings['whatsapp_number']) ?>" placeholder="Contoh: 6281234567890" required style="font-weight: 700; font-size: 1.05rem;">
      <div style="font-size: 0.8rem; color: var(--admin-text-muted); margin-top: 4px;">
        💡 <strong>Petunjuk:</strong> Gunakan format kode negara (contoh <code>6281234567890</code>). Nomor ini akan otomatis menjadi tujuan seluruh pesanan yang dikirimkan pelanggan dari website.
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="igHandle">Username Instagram</label>
        <input type="text" id="igHandle" name="instagram_handle" class="form-control" value="<?= e($settings['instagram_handle']) ?>" placeholder="@krisuka.fakfak">
      </div>

      <div class="form-group">
        <label class="form-label" for="igUrl">URL Profil Instagram</label>
        <input type="url" id="igUrl" name="instagram_url" class="form-control" value="<?= e($settings['instagram_url']) ?>" placeholder="https://instagram.com/...">
      </div>
    </div>

    <h4 style="color: var(--admin-primary); margin: 24px 0 14px; padding-bottom: 6px; border-bottom: 1px solid var(--admin-border);">
      3. Lokasi &amp; Informasi Pengiriman
    </h4>

    <div class="form-group">
      <label class="form-label" for="storeAddr">Alamat Lengkap Pusat Produksi</label>
      <textarea id="storeAddr" name="store_address" class="form-control" rows="2"><?= e($settings['store_address']) ?></textarea>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label class="form-label" for="opHours">Jam Operasional Pelayanan</label>
        <input type="text" id="opHours" name="operational_hours" class="form-control" value="<?= e($settings['operational_hours']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="bankInfo">Informasi Nomor Rekening Bank</label>
        <input type="text" id="bankInfo" name="bank_info" class="form-control" value="<?= e($settings['bank_info']) ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="shipInfo">Keterangan Standar Packing &amp; Ekspedisi</label>
      <input type="text" id="shipInfo" name="shipping_info" class="form-control" value="<?= e($settings['shipping_info']) ?>">
    </div>

    <button type="submit" class="btn-action btn-action-primary" style="padding: 12px 28px; font-size: 1rem; margin-top: 10px;">
      Simpan Perubahan Pengaturan Toko
    </button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


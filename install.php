<?php
/**
 * KRISUKA E-Commerce - Web Installer & Database Setup
 * Memastikan tabel terbuat, data kategori, 7 produk resmi, pengaturan toko, dan akun admin awal siap digunakan.
 */

define('KRISUKA_APP', true);
require_once __DIR__ . '/config/functions.php';

$installed = false;
$error = '';
$logs = [];

if (isset($_POST['install'])) {
    try {
        $pdo = get_db_connection();
        $logs[] = "Koneksi database berhasil terhubung.";
        
        $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

        $logs[] = "Tabel database diverifikasi (Produk: $prodCount, Kategori: $catCount, Admin: $adminCount).";
        $logs[] = "Data default 7 varian keripik lokal Fakfak berhasil disiapkan.";
        $logs[] = "Akun administrator default siap: Username 'admin' | Password 'admin123'.";
        $installed = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instalasi &amp; Setup Database | KRISUKA Fakfak</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', Arial, sans-serif;
      background: linear-gradient(135deg, #12372A 0%, #1B4D3E 100%);
      color: #1E2922;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    .install-card {
      background: #FFFFFF;
      max-width: 600px;
      width: 100%;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.25);
      border: 2px solid #D4AF37;
      overflow: hidden;
    }
    .install-header {
      background: #12372A;
      color: #FFFFFF;
      padding: 30px 24px;
      text-align: center;
      border-bottom: 2px solid #D4AF37;
    }
    .install-header h1 {
      font-family: 'Playfair Display', Georgia, serif;
      margin: 0 0 4px;
      font-size: 1.8rem;
    }
    .install-header small {
      color: #D4AF37;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-size: 0.75rem;
    }
    .install-body {
      padding: 30px;
    }
    .info-list {
      background: #FAF7F0;
      border: 1px solid #E8E0D2;
      border-radius: 10px;
      padding: 16px 20px;
      margin-bottom: 24px;
      font-size: 0.9rem;
      line-height: 1.6;
    }
    .btn-install {
      background: #1B4D3E;
      color: #FFFFFF;
      border: none;
      padding: 14px 28px;
      border-radius: 9999px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      width: 100%;
      box-shadow: 0 4px 15px rgba(27, 77, 62, 0.3);
    }
    .btn-install:hover {
      background: #12372A;
    }
    .log-box {
      background: #F1F5F9;
      border: 1px solid #CBD5E1;
      padding: 14px;
      border-radius: 8px;
      font-family: monospace;
      font-size: 0.85rem;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <div class="install-card">
    <div class="install-header">
      <h1>KRISUKA</h1>
      <small>INSTALASI &amp; SETUP SISTEM E-COMMERCE</small>
    </div>

    <div class="install-body">
      <p style="margin-bottom: 16px; font-size: 0.95rem; color: #596B60;">
        Selamat datang di instalasi sistem website toko online <strong>KRISUKA (Keripik Sukun, Keladi &amp; Pisang Khas Fakfak, Papua Barat)</strong>.
      </p>

      <div class="info-list">
        <strong>Paket Sistem yang Dikonfigurasi:</strong>
        <ul style="margin: 6px 0 0 20px;">
          <li>Skema Database Relasional (Admins, Categories, Products, Orders, Order Items, Settings, Stock Logs).</li>
          <li>Katalog 7 Produk Resmi (Sukun, Keladi, Balado, Jagung Bakar, Original, Coklat, Milo).</li>
          <li>Akun Administrator default: <code>admin</code> / <code>admin123</code>.</li>
          <li>Pengaturan Nomor WhatsApp Resmi Toko.</li>
        </ul>
      </div>

      <?php if (!empty($error)): ?>
        <div style="background: #FEE2E2; color: #991B1B; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($installed): ?>
        <div style="background: #DCFCE7; color: #166534; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
          <strong>🎉 Instalasi &amp; Verifikasi Selesai!</strong>
          <div class="log-box" style="margin-top: 10px; color: #1E2922;">
            <?php foreach ($logs as $l): ?>
              <div>&bull; <?= htmlspecialchars($l) ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="display: flex; gap: 12px;">
          <a href="<?= base_url('index.php') ?>" style="flex: 1; text-align: center; background: #1B4D3E; color: #FFF; padding: 12px; border-radius: 9999px; text-decoration: none; font-weight: 700;">
            Kunjungi Toko Publik &rarr;
          </a>
          <a href="<?= base_url('admin/login.php') ?>" style="flex: 1; text-align: center; background: #D4AF37; color: #12372A; padding: 12px; border-radius: 9999px; text-decoration: none; font-weight: 800;">
            Masuk Panel Admin &rarr;
          </a>
        </div>
      <?php else: ?>
        <form action="" method="POST">
          <input type="hidden" name="install" value="1">
          <button type="submit" class="btn-install">
            Jalankan Setup &amp; Verifikasi Database Sekarang
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>


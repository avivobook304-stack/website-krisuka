<?php
if (!defined('KRISUKA_APP')) {
    require_once __DIR__ . '/../config/functions.php';
}

$pageTitle = $pageTitle ?? 'KRISUKA | Keripik Sukun, Keladi & Pisang Khas Fakfak';
$pageDesc = $pageDesc ?? 'Toko Online Resmi KRISUKA - Keripik Sukun, Keladi, dan Pisang Aneka Rasa Khas Fakfak, Papua Barat. Renyah, Lezat, Cita Rasa Lokal.';
$cartTotals = cart_totals();
$storeWa = get_setting('whatsapp_number', '6282274761719');
$storeName = get_setting('store_name', 'KRISUKA');
$storeAbbr = get_setting('store_abbreviation', 'Keripik Sukun dan Keladi');
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDesc) ?>">
  <meta name="keywords" content="KRISUKA, keripik sukun fakfak, keripik keladi papua, keripik pisang fakfak, oleh-oleh papua barat">
  <title><?= e($pageTitle) ?></title>

  <!-- Google Fonts: Plus Jakarta Sans & Playfair Display -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">

  <!-- Open Graph & Social Media Preview (WhatsApp / Facebook / Twitter) -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDesc) ?>">
  <meta property="og:image" content="<?= e($ogImage ?? 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=1200&q=80') ?>">
  <meta property="og:site_name" content="KRISUKA Fakfak">
  <meta name="twitter:card" content="summary_large_image">
</head>
<body>

  <!-- Announcement Bar -->
  <aside class="announcement-bar" aria-label="Pengumuman Toko">
    🌿 <span class="gold-accent"><?= e($storeName) ?> Fakfak, Papua Barat:</span> <?= e($storeAbbr) ?> • Renyah, Lezat, Cita Rasa Lokal • <a href="<?= base_url('order-status.php') ?>" style="color: #FDE047; text-decoration: underline; font-weight: 700; margin-left: 6px;">Lacak Pesanan Anda &rarr;</a>
  </aside>

  <!-- Header & Navbar -->
  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar" aria-label="Navigasi Utama">
        <!-- Brand Logo -->
        <a href="<?= base_url('index.php') ?>" class="brand-logo" aria-label="<?= e($storeName) ?> Beranda">
          <div class="logo-emblem" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
          </div>
          <div class="brand-text">
            <span class="brand-title"><?= e($storeName) ?></span>
            <span class="brand-tagline">FAKFAK • PAPUA BARAT</span>
          </div>
        </a>

        <!-- Menu Navigation -->
        <ul class="nav-menu" id="navMenu">
          <li><a href="<?= base_url('index.php') ?>" class="nav-link <?= ($activeNav === 'home') ? 'active' : '' ?>">Beranda</a></li>
          <li><a href="<?= base_url('products.php') ?>" class="nav-link <?= ($activeNav === 'products') ? 'active' : '' ?>">Katalog Produk</a></li>
          <li><a href="<?= base_url('about.php') ?>" class="nav-link <?= ($activeNav === 'about') ? 'active' : '' ?>">Tentang Kami</a></li>
          <li><a href="<?= base_url('contact.php') ?>" class="nav-link <?= ($activeNav === 'contact') ? 'active' : '' ?>">Kontak</a></li>
        </ul>

        <!-- Action Buttons -->
        <div class="nav-actions">
          <button type="button" class="nav-search-btn" id="navSearchToggle" aria-label="Cari Produk" onclick="toggleNavSearch();" title="Cari varian keripik">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>

          <a href="<?= base_url('cart.php') ?>" class="cart-trigger-btn" aria-label="Lihat Keranjang Belanja">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span>Keranjang</span>
            <span class="cart-badge" id="cartBadgeCount"><?= (int)$cartTotals['total_qty'] ?></span>
          </a>

          <button class="mobile-toggle-btn" id="mobileMenuToggle" aria-label="Buka Menu Navigasi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
        </div>
      </nav>
    </div>

    <!-- Nav Search Expandable Bar -->
    <div class="nav-search-bar" id="navSearchBar" style="display: none;">
      <div class="container">
        <form action="<?= base_url('products.php') ?>" method="GET" class="nav-search-form">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="q" id="navSearchInput" placeholder="Ketik nama keripik yang dicari (contoh: sukun, keladi, coklat)..." autocomplete="off">
          <button type="submit" class="btn btn-primary" style="padding: 7px 18px; font-size: 0.85rem; border-radius: var(--radius-pill);">Cari</button>
          <button type="button" class="nav-search-close" onclick="toggleNavSearch();" aria-label="Tutup pencarian">&times;</button>
        </form>
      </div>
    </div>
  </header>


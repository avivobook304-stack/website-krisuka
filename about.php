<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'about';
$pageTitle = 'Tentang Kami | KRISUKA - Keripik Sukun & Keladi Fakfak';

$storeName = get_setting('store_name', 'KRISUKA');
$storeAbbr = get_setting('store_abbreviation', 'Keripik Sukun dan Keladi');

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 40px 0 20px; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <span class="section-tag">Mengenal Lebih Dekat</span>
    <h1 class="section-title" style="margin-bottom: 8px;">Tentang Brand KRISUKA</h1>
    <p class="section-desc">Cerita dedikasi mengolah kekayaan alam Fakfak, Papua Barat menjadi camilan lokal premium yang renyah dan dicintai.</p>
  </div>
</div>

<section style="padding: 60px 0 80px;">
  <div class="container">
    <div class="about-grid" style="margin-bottom: 60px;">
      <div class="about-image-wrapper">
        <div class="about-gold-frame"></div>
        <img 
          src="https://images.unsplash.com/photo-1528825871115-3581a5387919?auto=format&fit=crop&w=800&q=80" 
          alt="Hasil Alam Fakfak Papua Barat" 
          class="about-main-img"
        >
      </div>

      <div class="about-content-card">
        <span class="section-tag">Asal Mula Nama</span>
        <h2><?= e($storeName) ?>: <?= e($storeAbbr) ?></h2>
        <p>
          Lahir dari bumi Kabupaten Fakfak, Papua Barat yang tersohor akan kesuburan tanah dan alamnya yang asri, <strong>KRISUKA</strong> hadir sebagai wujud nyata kebanggaan atas komoditas lokal. 
        </p>
        <p>
          Nama <strong>KRISUKA</strong> berakar dari dua tanaman pangan unggulan Fakfak, yaitu <strong>Keripik Sukun</strong> dan <strong>Keladi</strong> (talas lokal Papua). Tak hanya itu, kami melengkapinya dengan aneka varian <strong>Keripik Pisang</strong> gurih, pedas balado, dan manis lumer yang sangat diminati masyarakat.
        </p>
        <p>
          Kami bertekad memperkenalkan keunikan rasa dan kerenyahan camilan asli Fakfak ke seluruh pelosok Indonesia tanpa melupakan kesejahteraan petani lokal yang menjadi mitra utama kami.
        </p>
      </div>
    </div>

    <!-- Values & Pillars Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
      <div class="about-highlight-box" style="padding: 24px;">
        <span style="font-size: 2rem; display: block; margin-bottom: 8px;">🌴</span>
        <strong style="font-size: 1.1rem; margin-bottom: 6px;">Bahan Pilihan Segar</strong>
        <span>Buah sukun yang matang pas, umbi keladi padat tanpa rasa gatal, dan pisang lokal kualitas super langsung dari kebun Fakfak.</span>
      </div>

      <div class="about-highlight-box" style="padding: 24px;">
        <span style="font-size: 2rem; display: block; margin-bottom: 8px;">✨</span>
        <strong style="font-size: 1.1rem; margin-bottom: 6px;">Teknik Penggorengan Tepat</strong>
        <span>Diiris tipis dengan ketebalan presisi dan digoreng pada suhu stabil sehingga renyah tahan lama tanpa minyak berlebih.</span>
      </div>

      <div class="about-highlight-box" style="padding: 24px;">
        <span style="font-size: 2rem; display: block; margin-bottom: 8px;">🤝</span>
        <strong style="font-size: 1.1rem; margin-bottom: 6px;">Dukung UMKM &amp; Petani</strong>
        <span>Setiap bungkus keripik KRISUKA yang Anda nikmati turut mendukung perputaran roda ekonomi petani lokal di Fakfak.</span>
      </div>

      <div class="about-highlight-box" style="padding: 24px;">
        <span style="font-size: 2rem; display: block; margin-bottom: 8px;">📦</span>
        <strong style="font-size: 1.1rem; margin-bottom: 6px;">Pengemasan Rapi &amp; Aman</strong>
        <span>Dikemas higienis dengan seal rapat agar kerenyahan dan kesegaran rasa terjaga sampai ke tangan Anda.</span>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


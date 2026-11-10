<?php
require_once __DIR__ . '/config/functions.php';

$activeNav = 'contact';
$pageTitle = 'Kontak Kami | KRISUKA Fakfak';

$storeWa = get_setting('whatsapp_number', '6282274761719');
$storeAddress = get_setting('store_address', 'Fakfak, Papua Barat, Indonesia');
$instagramUrl = get_setting('instagram_url', 'https://www.instagram.com/krisuka.fakfak?utm_source=ig_web_button_share_sheet&cplk=ZDNlZDc0MzIxNw==');
$instagramHandle = get_setting('instagram_handle', '@krisuka.fakfak');
$operationalHours = get_setting('operational_hours', 'Senin - Sabtu: 08.00 - 20.00 WIT');

include __DIR__ . '/includes/header.php';
?>

<div style="background: var(--color-cream-warm); padding: 40px 0 20px; border-bottom: 1px solid var(--color-cream-border);">
  <div class="container">
    <span class="section-tag">Layanan Pelanggan</span>
    <h1 class="section-title" style="margin-bottom: 8px;">Hubungi KRISUKA</h1>
    <p class="section-desc">Pusat informasi, pemesanan partai besar, dan layanan bantuan pelanggan keripik lokal khas Fakfak.</p>
  </div>
</div>

<section style="padding: 60px 0 80px;">
  <div class="container">
    <div class="contact-page-grid">
      <!-- Contact Details -->
      <div>
        <h3 style="font-family: var(--font-heading); font-size: 1.8rem; color: var(--color-primary-dark); margin-bottom: 20px;">
          Saluran Komunikasi Resmi
        </h3>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.7; margin-bottom: 30px;">
          Kami siap melayani kebutuhan camilan harian, pesanan oleh-oleh wisatawan, maupun pengadaan snack untuk acara instansi di Fakfak dan sekitarnya.
        </p>

        <div style="display: flex; flex-direction: column; gap: 20px;">
          <div style="display: flex; align-items: flex-start; gap: 16px; background: #FFFFFF; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-cream-border);">
            <div class="pillar-icon-box" style="margin: 0;">💬</div>
            <div>
              <strong style="display: block; color: var(--color-primary-dark); font-size: 1rem; margin-bottom: 4px;">WhatsApp Pemesanan</strong>
              <span style="color: var(--color-text-muted); font-size: 0.9rem; display: block; margin-bottom: 8px;">+<?= e($storeWa) ?></span>
              <a href="https://wa.me/<?= e($storeWa) ?>" target="_blank" class="contact-action-btn" style="padding: 6px 14px; font-size: 0.8rem;">
                Chat Langsung &rarr;
              </a>
            </div>
          </div>

          <div style="display: flex; align-items: flex-start; gap: 16px; background: #FFFFFF; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-cream-border);">
            <div class="pillar-icon-box" style="margin: 0;">📸</div>
            <div>
              <strong style="display: block; color: var(--color-primary-dark); font-size: 1rem; margin-bottom: 4px;">Instagram Resmi</strong>
              <span style="color: var(--color-text-muted); font-size: 0.9rem; display: block; margin-bottom: 8px;"><?= e($instagramHandle) ?></span>
              <a href="<?= e($instagramUrl) ?>" target="_blank" class="contact-action-btn" style="padding: 6px 14px; font-size: 0.8rem;">
                Buka Profil &rarr;
              </a>
            </div>
          </div>

          <div style="display: flex; align-items: flex-start; gap: 16px; background: #FFFFFF; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-cream-border);">
            <div class="pillar-icon-box" style="margin: 0;">📍</div>
            <div>
              <strong style="display: block; color: var(--color-primary-dark); font-size: 1rem; margin-bottom: 4px;">Lokasi Usaha</strong>
              <span style="color: var(--color-text-muted); font-size: 0.9rem; display: block;"><?= e($storeAddress) ?></span>
              <span style="color: var(--color-gold-hover); font-weight: 700; font-size: 0.82rem; display: block; margin-top: 4px;">Jam Buka: <?= e($operationalHours) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Inquiry Form -->
      <div style="background: #FFFFFF; border-radius: var(--radius-lg); border: 1px solid var(--color-cream-border); padding: 32px; box-shadow: var(--shadow-sm);">
        <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-primary-dark); margin-bottom: 12px;">
          Kirim Pertanyaan Cepat
        </h3>
        <p style="color: var(--color-text-muted); font-size: 0.88rem; margin-bottom: 24px;">
          Isi pesan di bawah ini untuk langsung diteruskan ke WhatsApp layanan pelanggan kami.
        </p>

        <form onsubmit="sendInquiryToWa(event);">
          <div class="form-group">
            <label class="form-label" for="inqName">Nama Anda <span class="required">*</span></label>
            <input type="text" id="inqName" class="form-control" placeholder="Nama lengkap Anda" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="inqTopic">Topik Pertanyaan</label>
            <select id="inqTopic" class="form-control">
              <option value="Tanya Stok Produk">Tanya Stok Produk</option>
              <option value="Pemesanan Partai Besar / Grosir">Pemesanan Partai Besar / Grosir</option>
              <option value="Info Pengiriman ke Luar Kota">Info Pengiriman ke Luar Kota</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="inqMessage">Pesan Anda <span class="required">*</span></label>
            <textarea id="inqMessage" class="form-control" placeholder="Tuliskan pertanyaan Anda..." required style="min-height: 110px;"></textarea>
          </div>

          <button type="submit" class="btn-checkout" style="cursor: pointer; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            Kirim via WhatsApp
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
function sendInquiryToWa(e) {
  e.preventDefault();
  const name = document.getElementById('inqName').value.trim();
  const topic = document.getElementById('inqTopic').value;
  const msg = document.getElementById('inqMessage').value.trim();
  
  const text = `Halo KRISUKA Fakfak! 👋\n\nNama: ${name}\nTopik: ${topic}\nPesan: ${msg}\n\nMohon informasinya ya min. Terima kasih!`;
  const url = `https://wa.me/<?= e($storeWa) ?>?text=${encodeURIComponent(text)}`;
  window.open(url, '_blank');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


/**
 * KRISUKA E-Commerce - Client Interactivity Script
 */

document.addEventListener('DOMContentLoaded', () => {
  // Mobile Navigation Toggle
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const navMenu = document.getElementById('navMenu');
  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (navMenu.classList.contains('open') && !navMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
        navMenu.classList.remove('open');
      }
    });

    // Close when clicking a nav link
    const navLinks = navMenu.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
      link.addEventListener('click', () => navMenu.classList.remove('open'));
    });
  }

  // Back to Top Button
  const backToTopBtn = document.getElementById('backToTopBtn');
  window.addEventListener('scroll', () => {
    if (backToTopBtn) {
      if (window.scrollY > 400) {
        backToTopBtn.classList.add('visible');
      } else {
        backToTopBtn.classList.remove('visible');
      }
    }
  }, { passive: true });

  if (backToTopBtn) {
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // FAQ Accordion
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const btn = item.querySelector('.faq-question-btn');
    const panel = item.querySelector('.faq-answer-panel');
    if (btn && panel) {
      btn.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        
        // Close all other items
        faqItems.forEach(other => {
          other.classList.remove('active');
          const otherPanel = other.querySelector('.faq-answer-panel');
          if (otherPanel) otherPanel.style.maxHeight = null;
        });

        // Toggle current item
        if (!isActive) {
          item.classList.add('active');
          panel.style.maxHeight = panel.scrollHeight + 'px';
        }
      });
    }
  });

  // Drawer Cart Open & Close
  const openCartBtn = document.getElementById('openCartBtn');
  const closeCartBtn = document.getElementById('closeCartBtn');
  const cartDrawer = document.getElementById('cartDrawer');
  const cartBackdrop = document.getElementById('cartBackdrop');

  function openCart() {
    if (cartDrawer && cartBackdrop) {
      cartBackdrop.classList.add('active');
      cartDrawer.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeCart() {
    if (cartDrawer && cartBackdrop) {
      cartBackdrop.classList.remove('active');
      cartDrawer.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  if (openCartBtn) openCartBtn.addEventListener('click', openCart);
  if (closeCartBtn) closeCartBtn.addEventListener('click', closeCart);
  if (cartBackdrop) cartBackdrop.addEventListener('click', closeCart);
});

// Toggle Nav Search Bar
function toggleNavSearch() {
  const bar = document.getElementById('navSearchBar');
  const input = document.getElementById('navSearchInput');
  if (!bar) return;
  if (bar.style.display === 'none' || !bar.style.display) {
    bar.style.display = 'block';
    if (input) {
      setTimeout(() => input.focus(), 80);
    }
  } else {
    bar.style.display = 'none';
  }
}

// Toast Notification
function showToast(message, icon = '✅') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast-card';
  toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => toast.classList.add('show'), 50);

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 400);
  }, 3200);
}

// Stepper Quantity on Cards
function stepCardQty(productId, step) {
  const input = document.getElementById(`qty-input-${productId}`);
  if (!input) return;
  let val = parseInt(input.value, 10) || 1;
  val += step;
  if (val < 1) val = 1;
  if (val > 99) val = 99;
  input.value = val;
}

// Fitur Deteksi Lokasi Terkini (GPS Geolocation)
function getLocationCurrent() {
  const statusEl = document.getElementById('locationStatus');
  const textarea = document.getElementById('buyerAddress');
  const btn = document.getElementById('btnGetLocation');

  if (!navigator.geolocation) {
    if (statusEl) {
      statusEl.style.display = 'block';
      statusEl.style.color = '#C53030';
      statusEl.innerHTML = '❌ Browser Anda tidak mendukung fitur pendeteksi lokasi (Geolocation).';
    } else {
      alert('Browser Anda tidak mendukung fitur pendeteksi lokasi GPS.');
    }
    return;
  }

  if (statusEl) {
    statusEl.style.display = 'block';
    statusEl.style.color = '#1B4D3E';
    statusEl.innerHTML = '⏳ Sedang mendeteksi titik koordinat GPS lokasi Anda...';
  }
  if (btn) btn.disabled = true;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      const mapsUrl = `https://maps.google.com/?q=${lat},${lng}`;
      
      let addressText = `Titik GPS: ${mapsUrl}`;

      // Coba reverse geocoding via OpenStreetMap Nominatim
      try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          const data = await response.json();
          if (data && data.display_name) {
            addressText = `${data.display_name}\n\n📍 Koordinat GPS: ${mapsUrl}`;
          }
        }
      } catch (err) {
        addressText = `Lokasi Terkini Pelanggan\n📍 Titik GPS: ${mapsUrl}`;
      }

      if (textarea) {
        textarea.value = addressText;
        textarea.focus();
      }

      if (statusEl) {
        statusEl.style.color = '#15803D';
        statusEl.innerHTML = `✅ Lokasi berhasil dideteksi! (<a href="${mapsUrl}" target="_blank" style="text-decoration: underline; color: #15803D; font-weight: bold;">Buka Titik Peta</a>)`;
      }
      if (btn) btn.disabled = false;
    },
    (error) => {
      let msg = 'Gagal mendeteksi lokasi.';
      switch (error.code) {
        case error.PERMISSION_DENIED:
          msg = '⚠️ Izin akses lokasi ditolak oleh pengguna. Silakan aktifkan izin lokasi di browser Anda.';
          break;
        case error.POSITION_UNAVAILABLE:
          msg = '⚠️ Informasi lokasi tidak tersedia saat ini.';
          break;
        case error.TIMEOUT:
          msg = '⚠️ Waktu permintaan deteksi lokasi habis. Silakan coba lagi.';
          break;
      }
      if (statusEl) {
        statusEl.style.color = '#C53030';
        statusEl.innerHTML = msg;
      } else {
        alert(msg);
      }
      if (btn) btn.disabled = false;
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}


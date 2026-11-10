<?php
/**
 * KRISUKA E-Commerce - Core System Helpers & Functions
 */

if (!defined('KRISUKA_APP')) {
    define('KRISUKA_APP', true);
}

// Inisialisasi PHP Session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    // Pastikan session directory lokal aman dan dapat ditulis
    $sessionPath = sys_get_temp_dir() . '/krisuka_sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        @session_save_path($sessionPath);
    }
    @session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Mendapatkan koneksi database PDO
 */
function db(): PDO {
    return get_db_connection();
}

/**
 * Base URL helper
 */
function base_url(string $path = ''): string {
    // Deteksi port dan path dinamis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Tentukan root path relatif ke /krisuka
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseFolder = '/krisuka';
    if (strpos($scriptName, '/krisuka') === false) {
        $baseFolder = '';
    }
    
    $cleanPath = ltrim($path, '/');
    return $protocol . $host . $baseFolder . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Asset URL helper
 */
function asset_url(string $path): string {
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Sanitasi string dan cegah XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $data): string {
    return trim(strip_tags($data));
}

/**
 * Format Rupiah rapi (Rp 10.000)
 */
function format_rupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Ambil nilai pengaturan toko
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    
    if ($settingsCache === null) {
        try {
            $stmt = db()->query("SELECT setting_key, setting_value FROM store_settings");
            $settingsCache = [];
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    
    return $settingsCache[$key] ?? $default;
}

/**
 * Perbarui atau simpan pengaturan toko
 */
function update_setting(string $key, string $value): bool {
    try {
        $stmt = db()->prepare("INSERT INTO store_settings (setting_key, setting_value, updated_at) 
            VALUES (:key, :val, :now)
            ON DUPLICATE KEY UPDATE setting_value = :val2, updated_at = :now2");
        
        $now = date('Y-m-d H:i:s');
        return $stmt->execute([
            ':key' => $key,
            ':val' => $value,
            ':now' => $now,
            ':val2' => $value,
            ':now2' => $now
        ]);
    } catch (Exception $e) {
        // Fallback untuk SQLite
        try {
            $stmt = db()->prepare("INSERT OR REPLACE INTO store_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)");
            return $stmt->execute([$key, $value, date('Y-m-d H:i:s')]);
        } catch (Exception $e2) {
            return false;
        }
    }
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['krisuka_csrf'])) {
        $_SESSION['krisuka_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['krisuka_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['krisuka_csrf'] ?? '', $token);
}

function validate_csrf(): bool {
    return verify_csrf();
}

/**
 * Flash Message System (Alert notifikasi sukses / error)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

function flash_message(): string {
    $flash = get_flash();
    if (!$flash) return '';
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    
    $colors = [
        'success' => ['bg' => '#DCFCE7', 'border' => '#86EFAC', 'text' => '#15803D', 'icon' => '✅'],
        'danger'  => ['bg' => '#FEE2E2', 'border' => '#FCA5A5', 'text' => '#991B1B', 'icon' => '⚠️'],
        'warning' => ['bg' => '#FEF3C7', 'border' => '#FCD34D', 'text' => '#92400E', 'icon' => '🔔'],
        'info'    => ['bg' => '#E0F2FE', 'border' => '#BAE6FD', 'text' => '#075985', 'icon' => 'ℹ️']
    ];
    $c = $colors[$type] ?? $colors['info'];

    return '<div style="background: ' . $c['bg'] . '; border: 1px solid ' . $c['border'] . '; color: ' . $c['text'] . '; padding: 14px 18px; border-radius: var(--radius-md, 8px); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-size: 0.94rem; box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));">'
        . '<span>' . $c['icon'] . '</span>'
        . '<span>' . e($message) . '</span>'
        . '</div>';
}

/**
 * Authentication Admin
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['krisuka_admin']['id']);
}

function current_admin(): ?array {
    return $_SESSION['krisuka_admin'] ?? null;
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        set_flash('danger', 'Silakan masuk terlebih dahulu untuk mengakses dashboard admin.');
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

/**
 * CART MANAGEMENT (Session + LocalStorage Sync)
 */
function cart_init(): void {
    if (!isset($_SESSION['krisuka_cart']) || !is_array($_SESSION['krisuka_cart'])) {
        $_SESSION['krisuka_cart'] = [];
    }
}

function cart_get(): array {
    cart_init();
    return $_SESSION['krisuka_cart'];
}

function cart_add(int $productId, int $qty = 1): bool {
    cart_init();
    $stmt = db()->prepare("SELECT id, name, price, netto, image, stock FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();
    if (!$prod) return false;

    if (isset($_SESSION['krisuka_cart'][$productId])) {
        $_SESSION['krisuka_cart'][$productId]['qty'] += $qty;
    } else {
        $_SESSION['krisuka_cart'][$productId] = [
            'id' => (int)$prod['id'],
            'name' => $prod['name'],
            'price' => (float)$prod['price'],
            'netto' => $prod['netto'],
            'image' => $prod['image'],
            'qty' => $qty
        ];
    }
    return true;
}

function cart_update(int $productId, int $qty): void {
    cart_init();
    if ($qty <= 0) {
        cart_remove($productId);
    } elseif (isset($_SESSION['krisuka_cart'][$productId])) {
        $_SESSION['krisuka_cart'][$productId]['qty'] = $qty;
    }
}

function cart_remove(int $productId): void {
    cart_init();
    unset($_SESSION['krisuka_cart'][$productId]);
}

function cart_clear(): void {
    $_SESSION['krisuka_cart'] = [];
}

function cart_totals(): array {
    $cart = cart_get();
    $totalQty = 0;
    $totalPrice = 0.00;

    foreach ($cart as $item) {
        $totalQty += (int)$item['qty'];
        $totalPrice += ((float)$item['price'] * (int)$item['qty']);
    }

    return [
        'count' => count($cart),
        'total_qty' => $totalQty,
        'total_price' => $totalPrice
    ];
}

/**
 * Generate Order Code Unik (contoh: KSU-20261110-7B4F)
 */
function generate_order_code(): string {
    return 'KSU-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

/**
 * Mendapatkan Label Ramah Pengguna untuk Metode Pembayaran
 */
function payment_method_label(?string $key): string {
    switch ($key) {
        case 'bank_transfer':
            return 'Transfer Bank (BCA / BRI / Mandiri)';
        case 'qris':
            return 'QRIS (Semua Bank & E-Wallet)';
        case 'cod':
            return 'Bayar di Tempat / COD (Khusus Kota Fakfak)';
        case 'ewallet':
            return 'E-Wallet (DANA / OVO / GoPay / ShopeePay)';
        default:
            return !empty($key) ? ucwords(str_replace('_', ' ', $key)) : 'Transfer Bank';
    }
}

/**
 * Format WhatsApp Order Message
 */
function build_whatsapp_order_message(array $order, array $items): string {
    $storeName = get_setting('store_name', 'KRISUKA');
    
    $itemLines = [];
    $computedSubtotal = 0;
    foreach ($items as $idx => $it) {
        $subtotal = $it['price'] * $it['qty'];
        $computedSubtotal += $subtotal;
        $itemLines[] = ($idx + 1) . ". *" . $it['product_name'] . "*\n   " . $it['qty'] . " bungkus x " . format_rupiah($it['price']) . " = " . format_rupiah($subtotal);
    }
    
    $itemsText = implode("\n\n", $itemLines);
    $notes = !empty($order['customer_notes']) ? "• *Catatan:* " . $order['customer_notes'] . "\n" : "";

    $subtotalVal = (float)($order['subtotal'] ?? $computedSubtotal);
    $discountVal = (float)($order['discount_amount'] ?? 0);
    $shippingVal = (float)($order['shipping_cost'] ?? 0);
    $shippingZone = trim($order['shipping_zone'] ?? '');

    $summaryLines = [];
    $summaryLines[] = "• Total Barang: {$order['total_qty']} bungkus";
    $summaryLines[] = "• Subtotal Produk: " . format_rupiah($subtotalVal);
    
    if ($discountVal > 0) {
        $vCode = !empty($order['voucher_code']) ? " (" . $order['voucher_code'] . ")" : "";
        $summaryLines[] = "• Diskon Kupon{$vCode}: -" . format_rupiah($discountVal);
    }
    
    if ($shippingZone !== '' || isset($order['shipping_cost'])) {
        $zoneLabel = $shippingZone ? " ({$shippingZone})" : "";
        $costLabel = ($shippingVal > 0) ? format_rupiah($shippingVal) : "GRATIS / Rp 0";
        $summaryLines[] = "• Ongkos Kirim{$zoneLabel}: " . $costLabel;
    }
    
    $summaryLines[] = "• *Total Pembayaran: " . format_rupiah($order['total_price']) . "*";
    $summaryText = implode("\n", $summaryLines);

    $payMethod = payment_method_label($order['payment_method'] ?? 'bank_transfer');

    // Penyesuaian kalimat penutup berdasarkan metode pembayaran yang dipilih
    $closingNote = "Mohon konfirmasi ketersediaan stok & informasi rekening pembayaran ya. Terima kasih! 🙏";
    if (($order['payment_method'] ?? '') === 'cod') {
        $closingNote = "Mohon konfirmasi ketersediaan stok & estimasi jadwal pengantaran kurir COD ke alamat ya. Terima kasih! 🙏";
    } elseif (($order['payment_method'] ?? '') === 'qris') {
        $closingNote = "Mohon konfirmasi ketersediaan stok & kirimkan kode QRIS untuk pembayarannya ya. Terima kasih! 🙏";
    } elseif (($order['payment_method'] ?? '') === 'ewallet') {
        $closingNote = "Mohon konfirmasi ketersediaan stok & nomor tujuan transfer E-Wallet toko ya. Terima kasih! 🙏";
    }

    return "Halo *$storeName (Fakfak, Papua Barat)*! 👋\n"
         . "Saya ingin memesan keripik lokal dengan kode pesanan *{$order['order_code']}*:\n\n"
         . "━━━━━━━━━━━━━━━━━━━━━\n"
         . "📦 *RINCIAN PESANAN:*\n"
         . "━━━━━━━━━━━━━━━━━━━━━\n"
         . "$itemsText\n\n"
         . "━━━━━━━━━━━━━━━━━━━━━\n"
         . "📊 *RINGKASAN TOTAL:*\n"
         . "$summaryText\n"
         . "━━━━━━━━━━━━━━━━━━━━━\n\n"
         . "👤 *DATA PEMBELI:*\n"
         . "• *Nama:* {$order['customer_name']}\n"
         . "• *WhatsApp:* {$order['customer_phone']}\n"
         . "• *Metode Bayar:* {$payMethod}\n"
         . "• *Alamat Pengiriman:* {$order['customer_address']}\n"
         . "$notes\n"
         . "$closingNote";
}

/**
 * Ambil Statistik Rating Produk (Rata-rata & Jumlah Ulasan)
 */
function get_product_rating_stats(int $productId): array {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) as total_reviews, COALESCE(AVG(rating), 5.0) as avg_rating 
                               FROM product_reviews 
                               WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int)($row['total_reviews'] ?? 0);
        $rating = ($count > 0) ? round((float)$row['avg_rating'], 1) : 5.0;
        return [
            'count' => $count,
            'rating' => $rating,
            'stars_html' => render_star_rating($rating)
        ];
    } catch (Exception $e) {
        return ['count' => 0, 'rating' => 5.0, 'stars_html' => '★★★★★'];
    }
}

/**
 * Render Bintang HTML Emas
 */
function render_star_rating(float $rating): string {
    $full = min(5, max(0, floor($rating)));
    $half = ($rating - $full >= 0.5) ? 1 : 0;
    $empty = 5 - $full - $half;
    
    $html = '<span class="star-rating" style="color: #F59E0B; letter-spacing: 1.5px; font-size: 0.95em;">';
    for ($i = 0; $i < $full; $i++) $html .= '★';
    if ($half) $html .= '★';
    for ($i = 0; $i < $empty; $i++) $html .= '☆';
    $html .= '</span>';
    return $html;
}

/**
 * Resolves absolute URL for product image (handles external URLs and local uploads)
 */
function product_image_url(?string $image): string {
    if (empty($image)) {
        return 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80';
    }
    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }
    return base_url(ltrim($image, '/'));
}

/**
 * Handle Upload Gambar Produk dengan Validasi Keamanan Ketat
 */
function upload_product_image(array $file): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validasi Ukuran (Maksimal 3MB)
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new Exception("Ukuran gambar maksimal adalah 3 MB.");
    }

    // Validasi Ekstensi dan MIME Type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        throw new Exception("Format gambar harus berupa JPG, PNG, atau WEBP.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes)) {
        throw new Exception("Tipe file tidak valid sebagai gambar.");
    }

    // Buat nama file unik yang aman
    $filename = 'krisuka_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/img/products/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Gagal menyimpan file gambar ke server.");
    }

    return 'assets/img/products/' . $filename;
}

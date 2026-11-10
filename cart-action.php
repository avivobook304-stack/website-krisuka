<?php
require_once __DIR__ . '/config/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_GET['ajax']) && $_GET['ajax'] == 1);

// Cek Kupon Voucher (Bisa diakses via AJAX tanpa blokir)
if ($action === 'check_voucher') {
    header('Content-Type: application/json');
    $code = strtoupper(trim(sanitize($_POST['code'] ?? $_GET['code'] ?? '')));
    $totals = cart_totals();
    $subtotal = $totals['total_price'];

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Silakan ketikkan kode kupon diskon.']);
        exit;
    }

    $stmt = db()->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => "Kode kupon '{$code}' tidak valid atau belum terdaftar."]);
        exit;
    }

    $today = date('Y-m-d');
    if ($voucher['valid_until'] < $today) {
        echo json_encode(['success' => false, 'message' => "Kupon '{$code}' telah kedaluwarsa pada " . date('d/m/Y', strtotime($voucher['valid_until'])) . "."]);
        exit;
    }

    if ($voucher['used_count'] >= $voucher['usage_limit']) {
        echo json_encode(['success' => false, 'message' => "Kuota pemakaian kupon '{$code}' telah habis."]);
        exit;
    }

    if ($subtotal < (float)$voucher['min_spend']) {
        echo json_encode([
            'success' => false, 
            'message' => "Minimal belanja untuk kupon ini adalah " . format_rupiah($voucher['min_spend']) . " (Subtotal saat ini: " . format_rupiah($subtotal) . ")."
        ]);
        exit;
    }

    // Hitung diskon
    $discount = 0;
    if ($voucher['discount_type'] === 'percent') {
        $discount = ($subtotal * (float)$voucher['discount_value']) / 100;
        if ((float)$voucher['max_discount'] > 0 && $discount > (float)$voucher['max_discount']) {
            $discount = (float)$voucher['max_discount'];
        }
    } else {
        $discount = min($subtotal, (float)$voucher['discount_value']);
    }

    echo json_encode([
        'success' => true,
        'code' => $voucher['code'],
        'discount_amount' => $discount,
        'formatted_discount' => format_rupiah($discount),
        'message' => "Kupon '{$voucher['code']}' aktif! Anda berhak mendapatkan diskon " . format_rupiah($discount) . "."
    ]);
    exit;
}

// Verifikasi CSRF untuk request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan muat ulang halaman.']);
        exit;
    }
    set_flash('danger', 'Validasi sesi gagal, silakan coba lagi.');
    header('Location: ' . base_url('cart.php'));
    exit;
}

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1));
        
        if ($productId > 0) {
            $added = cart_add($productId, $qty);
            if ($added) {
                set_flash('success', 'Produk berhasil ditambahkan ke keranjang belanja!');
            } else {
                set_flash('danger', 'Produk tidak ditemukan atau sedang tidak aktif.');
            }
        }
        break;

    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($productId > 0) {
            cart_update($productId, $qty);
            set_flash('info', 'Jumlah produk di keranjang telah diperbarui.');
        }
        break;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($productId > 0) {
            cart_remove($productId);
            set_flash('info', 'Produk telah dihapus dari keranjang.');
        }
        break;

    case 'clear':
        cart_clear();
        set_flash('info', 'Keranjang belanja Anda telah dikosongkan.');
        break;
}

if ($isAjax) {
    header('Content-Type: application/json');
    $totals = cart_totals();
    echo json_encode([
        'success' => true,
        'cart_count' => $totals['count'],
        'total_qty' => $totals['total_qty'],
        'total_price' => $totals['total_price'],
        'formatted_total' => format_rupiah($totals['total_price'])
    ]);
    exit;
}

// Jika tombol "Beli Sekarang" ditekan, langsung arahkan ke checkout
if (!empty($_POST['buy_now'])) {
    header('Location: ' . base_url('checkout.php'));
    exit;
}

// Ketika produk ditambahkan ke keranjang, otomatis langsung diarahkan ke halaman keranjang belanja
if ($action === 'add') {
    header('Location: ' . base_url('cart.php'));
    exit;
}

$referer = $_SERVER['HTTP_REFERER'] ?? base_url('cart.php');
header('Location: ' . $referer);
exit;


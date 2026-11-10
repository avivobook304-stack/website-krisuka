<?php
require_once __DIR__ . '/../config/functions.php';

require_admin();

$productId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!verify_csrf()) {
    set_flash('danger', 'Validasi sesi keamanan gagal.');
    header('Location: ' . base_url('admin/products.php'));
    exit;
}

if ($productId > 0) {
    $db = db();
    $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prodName = $stmt->fetchColumn();

    if ($prodName) {
        // Cek apakah produk pernah dipesan dalam order_items
        $orderedCount = (int)$db->query("SELECT COUNT(*) FROM order_items WHERE product_id = $productId")->fetchColumn();

        if ($orderedCount > 0) {
            // Jika ada riwayat transaksi, nonaktifkan produk agar data pesanan masa lalu tidak rusak
            $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$productId]);
            set_flash('info', "Produk '{$prodName}' memiliki riwayat transaksi, status produk dinonaktifkan (disembunyikan dari katalog).");
        } else {
            // Jika belum pernah dipesan, hapus dari database
            $db->prepare("DELETE FROM stock_logs WHERE product_id = ?")->execute([$productId]);
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
            set_flash('success', "Produk '{$prodName}' berhasil dihapus secara permanen.");
        }
    }
}

header('Location: ' . base_url('admin/products.php'));
exit;


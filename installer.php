<?php
/**
 * KRISUKA E-Commerce - Database Schema & Data Installer
 * Otomatis membuat tabel dan data inisial jika belum tersedia.
 */

if (!defined('KRISUKA_APP')) {
    define('KRISUKA_APP', true);
}

function krisuka_run_installer(PDO $pdo, string $driver = 'mysql') {
    $autoIncrement = ($driver === 'sqlite') ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
    $engine = ($driver === 'sqlite') ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    // 1. Tabel Admins
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY $autoIncrement,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'admin',
        created_at DATETIME NOT NULL
    ) $engine;");

    // 2. Tabel Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY $autoIncrement,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME NOT NULL
    ) $engine;");

    // 3. Tabel Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY $autoIncrement,
        category_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        badge VARCHAR(50) DEFAULT 'Terfavorit',
        price DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
        stock INTEGER NOT NULL DEFAULT 50,
        netto VARCHAR(50) DEFAULT '100 gram',
        description TEXT,
        image VARCHAR(255) NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        is_featured INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) $engine;");

    // 4. Tabel Orders
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY $autoIncrement,
        order_code VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(30) NOT NULL,
        customer_address TEXT NOT NULL,
        customer_notes TEXT,
        total_qty INTEGER NOT NULL DEFAULT 0,
        total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) DEFAULT 'whatsapp_transfer',
        order_status VARCHAR(30) DEFAULT 'menunggu_konfirmasi',
        whatsapp_url TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) $engine;");

    // 5. Tabel Order Items
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY $autoIncrement,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        qty INTEGER NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL
    ) $engine;");

    // 6. Tabel Store Settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME NOT NULL
    ) $engine;");

    // 7. Tabel Stock Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_logs (
        id INTEGER PRIMARY KEY $autoIncrement,
        product_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        quantity INTEGER NOT NULL,
        note VARCHAR(255),
        created_at DATETIME NOT NULL
    ) $engine;");

    // 8. Tabel Vouchers (Kupon Diskon)
    $pdo->exec("CREATE TABLE IF NOT EXISTS vouchers (
        id INTEGER PRIMARY KEY $autoIncrement,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_type VARCHAR(20) NOT NULL DEFAULT 'fixed',
        discount_value DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
        min_spend DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
        max_discount DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
        usage_limit INTEGER NOT NULL DEFAULT 100,
        used_count INTEGER NOT NULL DEFAULT 0,
        valid_until DATE NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL
    ) $engine;");

    // 9. Tabel Product Reviews & Suggestions (Rating & Saran Produk)
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INTEGER PRIMARY KEY $autoIncrement,
        product_id INTEGER NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_city VARCHAR(100) DEFAULT 'Fakfak',
        rating INTEGER NOT NULL DEFAULT 5,
        review_text TEXT NOT NULL,
        is_approved INTEGER NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL
    ) $engine;");

    // Pastikan kolom voucher_code, discount_amount, shipping_cost ada pada tabel orders
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN voucher_code VARCHAR(50) NULL");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(12,2) DEFAULT 0.00");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(12,2) DEFAULT 0.00");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_zone VARCHAR(100) NULL");
    } catch (Exception $e) {}

    // ==========================================
    // SEED DATA JIKA BELUM ADA
    // ==========================================

    // A. Seed Admin Default
    $stmtAdmin = $pdo->query("SELECT COUNT(*) FROM admins");
    if ((int)$stmtAdmin->fetchColumn() === 0) {
        $stmtInsertAdmin = $pdo->prepare("INSERT INTO admins (name, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsertAdmin->execute([
            'Administrator KRISUKA',
            'admin',
            'admin@krisuka.id',
            password_hash('admin123', PASSWORD_BCRYPT),
            'superadmin',
            date('Y-m-d H:i:s')
        ]);
    }

    // B. Seed Categories
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM categories");
    if ((int)$stmtCat->fetchColumn() === 0) {
        $categories = [
            ['Keripik Sukun', 'keripik-sukun', 'Cemilan gurih renyah berbahan buah sukun pilihan khas Fakfak Papua Barat.'],
            ['Keripik Keladi', 'keripik-keladi', 'Keripik umbi keladi/talas khas Fakfak dengan tekstur super renyah.'],
            ['Keripik Pisang', 'keripik-pisang', 'Keripik pisang lokal Fakfak aneka rasa istimewa gurih, pedas, dan manis.']
        ];
        $stmtInsertCat = $pdo->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, ?)");
        foreach ($categories as $c) {
            $stmtInsertCat->execute([$c[0], $c[1], $c[2], date('Y-m-d H:i:s')]);
        }
    }

    // C. Seed Store Settings
    $stmtSetting = $pdo->query("SELECT COUNT(*) FROM store_settings");
    if ((int)$stmtSetting->fetchColumn() === 0) {
        $settings = [
            'store_name' => 'KRISUKA',
            'store_abbreviation' => 'Keripik Sukun dan Keladi',
            'store_tagline' => 'Renyah, Lezat, Cita Rasa Lokal.',
            'store_origin' => 'Fakfak, Papua Barat',
            'whatsapp_number' => '6282274761719',
            'instagram_handle' => '@krisuka.fakfak',
            'instagram_url' => 'https://www.instagram.com/krisuka.fakfak?utm_source=ig_web_button_share_sheet&cplk=ZDNlZDc0MzIxNw==',
            'store_address' => 'Kabupaten Fakfak, Papua Barat, Indonesia',
            'operational_hours' => 'Senin - Sabtu: 08.00 - 20.00 WIT',
            'shipping_info' => 'Pengiriman dari Fakfak, Papua Barat ke seluruh Nusantara. Packing kardus tebal & bubble wrap aman.',
            'default_price' => '10000',
            'bank_info' => 'BCA: 123-456-7890 a.n KRISUKA | BRI: 0987-01-023456-50-1 a.n KRISUKA'
        ];
        $stmtInsertSet = $pdo->prepare("INSERT INTO store_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)");
        foreach ($settings as $k => $v) {
            $stmtInsertSet->execute([$k, $v, date('Y-m-d H:i:s')]);
        }
    }

    // D. Seed 7 Produk Resmi KRISUKA
    $stmtProd = $pdo->query("SELECT COUNT(*) FROM products");
    if ((int)$stmtProd->fetchColumn() === 0) {
        // Ambil ID Kategori
        $catPisang = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'keripik-pisang'")->fetchColumn();
        $catSukun = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'keripik-sukun'")->fetchColumn();
        $catKeladi = (int)$pdo->query("SELECT id FROM categories WHERE slug = 'keripik-keladi'")->fetchColumn();

        if (!$catPisang) $catPisang = 1;
        if (!$catSukun) $catSukun = 2;
        if (!$catKeladi) $catKeladi = 3;

        $products = [
            [
                $catSukun,
                'Keripik Sukun Fakfak',
                'keripik-sukun-fakfak',
                'Khas Fakfak Terfavorit',
                10000.00,
                60,
                '100 gram',
                'Oleh-oleh ikonik dari Fakfak, Papua Barat. Dibuat dari buah sukun segar berkualitas tinggi yang diiris presisi dan digoreng dengan minyak nabati higienis. Memiliki rasa gurih lembut mentega alami yang renyah dan bikin nagih di lidah.',
                'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80',
                1,
                1
            ],
            [
                $catKeladi,
                'Keripik Keladi Fakfak',
                'keripik-keladi-fakfak',
                'Gurih Renyah Alami',
                10000.00,
                55,
                '100 gram',
                'Keripik keladi (talas lokal) khas tanah Fakfak Papua. Irisan tipis yang digoreng garing menghasilkan sensasi kerenyahan tiada duanya, berpadu taburan garam gurih alami tanpa bahan pengawet.',
                'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=700&q=80',
                1,
                1
            ],
            [
                $catPisang,
                'Keripik Pisang Balado',
                'keripik-pisang-balado',
                'Pedas Manis Gurih',
                10000.00,
                70,
                '100 gram',
                'Sensasi bumbu balado khas yang pedas, manis, dan gurih meresap sempurna di setiap kepingan keripik pisang renyah hasil panen petani lokal Fakfak.',
                'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80',
                1,
                1
            ],
            [
                $catPisang,
                'Keripik Pisang Jagung Bakar',
                'keripik-pisang-jagung-bakar',
                'Aroma Bakar Gurih',
                10000.00,
                65,
                '100 gram',
                'Paduan rasa jagung bakar manis dan gurih dengan aroma bakar khas yang harum menggugah selera, favorit camilan santai keluarga.',
                'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=700&q=80',
                1,
                0
            ],
            [
                $catPisang,
                'Keripik Pisang Original',
                'keripik-pisang-original',
                'Renyah Asli & Alami',
                10000.00,
                80,
                '100 gram',
                'Rasa klasik keripik pisang gurih dengan taburan garam ringan, menonjolkan keaslian rasa manis dan gurih alami pisang Fakfak.',
                'https://images.unsplash.com/photo-1621447504864-d8686e12698c?auto=format&fit=crop&w=700&q=80',
                1,
                1
            ],
            [
                $catPisang,
                'Keripik Pisang Coklat',
                'keripik-pisang-coklat',
                'Manis Legit Coklat Tebal',
                10000.00,
                55,
                '100 gram',
                'Balutan coklat manis legit yang lumer menyelimuti kepingan pisang renyah, perpaduan manis coklat dan gurih yang pas di mulut.',
                'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=700&q=80',
                1,
                1
            ],
            [
                $catPisang,
                'Keripik Pisang Milo',
                'keripik-pisang-milo',
                'Coklat Malt Creamy',
                10000.00,
                50,
                '100 gram',
                'Sensasi taburan bubuk coklat malt Milo premium yang creamy dan wangi, menghadirkan cita rasa modern yang bikin ketagihan.',
                'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=700&q=80',
                1,
                0
            ]
        ];

        $stmtInsertProd = $pdo->prepare("INSERT INTO products 
            (category_id, name, slug, badge, price, stock, netto, description, image, is_active, is_featured, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($products as $p) {
            $now = date('Y-m-d H:i:s');
            $stmtInsertProd->execute([
                $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $now, $now
            ]);
        }
    }
}


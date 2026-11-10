<?php
require_once __DIR__ . '/../config/functions.php';

// Jika sudah login, langsung ke dashboard
if (is_admin_logged_in()) {
    header('Location: ' . base_url('admin/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Validasi keamanan gagal. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            // Rate limit delay 300ms untuk mencegah brute force
            usleep(300000);

            $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login Berhasil
                $_SESSION['krisuka_admin'] = [
                    'id' => (int)$admin['id'],
                    'name' => $admin['name'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'role' => $admin['role']
                ];

                set_flash('success', "Selamat datang kembali, {$admin['name']}!");
                header('Location: ' . base_url('admin/index.php'));
                exit;
            } else {
                $error = 'Username atau kata sandi tidak cocok.';
            }
        }
    }
}

$storeName = get_setting('store_name', 'KRISUKA');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin | <?= e($storeName) ?> Fakfak</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset_url('css/admin.css') ?>">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #12372A 0%, #1B4D3E 100%);
      padding: 20px;
    }
    .login-box {
      width: 100%;
      max-width: 420px;
      background: #FFFFFF;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.25);
      border: 2px solid #D4AF37;
      overflow: hidden;
    }
    .login-header {
      background: #12372A;
      padding: 30px 24px;
      text-align: center;
      color: #FFFFFF;
      border-bottom: 2px solid #D4AF37;
    }
    .login-header h2 {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 1.8rem;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }
    .login-header small {
      color: #D4AF37;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-size: 0.75rem;
    }
    .login-body {
      padding: 30px 28px;
    }
    .btn-login {
      width: 100%;
      background: #1B4D3E;
      color: #FFFFFF;
      padding: 13px;
      border-radius: 9999px;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(27, 77, 62, 0.3);
    }
    .btn-login:hover {
      background: #12372A;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <div class="login-box">
    <div class="login-header">
      <h2><?= e($storeName) ?></h2>
      <small>PANEL ADMINISTRATOR TOKO</small>
    </div>

    <div class="login-body">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('admin/login.php') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label" for="username">Username / Email</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: admin" required autofocus value="<?= e($_POST['username'] ?? 'admin') ?>">
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="password">Kata Sandi (Password)</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
          <div style="font-size: 0.78rem; color: #64748B; margin-top: 6px;">
            Default instalasi awal: <code>admin</code> / <code>admin123</code>
          </div>
        </div>

        <button type="submit" class="btn-login">
          Masuk ke Dashboard
        </button>
      </form>

      <div style="text-align: center; margin-top: 24px;">
        <a href="<?= base_url('index.php') ?>" style="font-size: 0.85rem; color: #64748B; font-weight: 600;">
          &larr; Kembali ke Beranda Toko
        </a>
      </div>
    </div>
  </div>

</body>
</html>


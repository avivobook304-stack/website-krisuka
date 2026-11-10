<?php
require_once __DIR__ . '/../config/functions.php';

$adminTitle = 'Manajemen Admin';
$adminNav = 'admins';

$errors = [];

// Handle Tambah Admin Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            $errors[] = 'Semua kolom wajib diisi.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Kata sandi minimal 6 karakter.';
        } else {
            // Cek apakah username sudah ada
            $check = db()->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                $errors[] = 'Username tersebut sudah digunakan oleh admin lain.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare("INSERT INTO admins (name, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, 'admin', ?)");
                $stmt->execute([$name, $username, $email, $hash, date('Y-m-d H:i:s')]);
                set_flash('success', "Akun admin baru '{$username}' berhasil dibuat!");
                header('Location: ' . base_url('admin/admins.php'));
                exit;
            }
        }
    }
}

// Handle Ganti Password Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf()) {
        $errors[] = 'Validasi sesi gagal.';
    } else {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';

        if ($adminId <= 0 || strlen($newPass) < 6) {
            $errors[] = 'Kata sandi baru minimal 6 karakter.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $adminId]);
            set_flash('success', 'Kata sandi admin berhasil diperbarui!');
            header('Location: ' . base_url('admin/admins.php'));
            exit;
        }
    }
}

// Ambil semua akun admin
$admins = db()->query("SELECT id, name, username, email, role, created_at FROM admins ORDER BY id ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start;">
  <!-- Form Tambah Admin Baru -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Tambah Admin Baru</h3>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul style="margin-left: 20px;">
          <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= base_url('admin/admins.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_admin">

      <div class="form-group">
        <label class="form-label" for="admName">Nama Lengkap Admin <span style="color:red;">*</span></label>
        <input type="text" id="admName" name="name" class="form-control" placeholder="Contoh: Siti Fatimah" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="admUser">Username <span style="color:red;">*</span></label>
        <input type="text" id="admUser" name="username" class="form-control" placeholder="Contoh: sitikrisuka" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="admEmail">Alamat Email <span style="color:red;">*</span></label>
        <input type="email" id="admEmail" name="email" class="form-control" placeholder="admin@krisuka.id" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="admPass">Kata Sandi Awal (Min 6 Karakter) <span style="color:red;">*</span></label>
        <input type="password" id="admPass" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-action btn-action-primary" style="padding: 10px 20px; width: 100%; justify-content: center;">
        Daftarkan Admin
      </button>
    </form>
  </div>

  <!-- Daftar Akun Admin -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Daftar Pengguna Administrator</h3>
    </div>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Nama Admin</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $adm): ?>
          <tr>
            <td><strong><?= e($adm['name']) ?></strong></td>
            <td><code><?= e($adm['username']) ?></code></td>
            <td><?= e($adm['email']) ?></td>
            <td><span class="badge-status badge-selesai"><?= e($adm['role']) ?></span></td>
            <td>
              <!-- Form Ganti Password Sederhana -->
              <form action="<?= base_url('admin/admins.php') ?>" method="POST" style="display: flex; gap: 4px;" onsubmit="return confirm('Ubah kata sandi admin <?= addslashes($adm['name']) ?>?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                <input type="password" name="new_password" placeholder="Sandi baru..." required style="padding: 4px 8px; font-size: 0.78rem; border: 1px solid var(--admin-border); border-radius: 4px; width: 110px;">
                <button type="submit" class="btn-action btn-action-edit" style="font-size: 0.75rem; padding: 4px 8px;">
                  Ganti
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


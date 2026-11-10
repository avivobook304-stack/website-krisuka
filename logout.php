<?php
require_once __DIR__ . '/../config/functions.php';

unset($_SESSION['krisuka_admin']);
set_flash('info', 'Anda telah berhasil keluar dari sistem admin.');
header('Location: ' . base_url('admin/login.php'));
exit;


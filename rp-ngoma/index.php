<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) { redirect(user()['role'] . '/'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db()->prepare('SELECT * FROM users WHERE username = ? AND role = ?');
    $s->execute([trim($_POST['username'] ?? ''), $_POST['role'] ?? '']);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true); unset($u['password_hash']); $_SESSION['user'] = $u;
        redirect($u['role'] . '/');
    }
    $error = 'Wrong role, username or password.';
}
page_start('Log in'); ?>
<div class="card narrow"><h2>Log in</h2>
<?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
<label for="role">I am a</label><select id="role" name="role"><option value="trainer">Trainer</option><option value="student">Student</option><option value="director">Director of Quality Assurance</option></select>
<label for="username">Username</label><input id="username" name="username" required autocomplete="username">
<label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password">
<p><button>Log in</button></p></form>
<p class="hint">Demo (password 1234): trainer1, trainer2, student1, student2, qa</p></div>
<?php page_end();

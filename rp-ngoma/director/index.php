<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/sms.php';
$u = require_role('director');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db()->prepare('SELECT up.*, t.full_name, t.phone FROM uploads up JOIN users t ON t.id=up.trainer_id WHERE up.id=? AND up.status="pending"');
    $s->execute([(int)($_POST['upload_id'] ?? 0)]); $x = $s->fetch();
    if ($x) {
        db()->prepare('UPDATE uploads SET status="confirmed", confirmed_by=?, confirmed_at=NOW() WHERE id=?')->execute([$u['id'], $x['id']]);
        $msg = sprintf('RP-Ngoma College: Dear %s, your attendance list "%s" (%s, %d rows) was received and confirmed by the Director of Quality Assurance on %s.',
            $x['full_name'], $x['file_name'], $x['modules'], $x['row_count'], date('d M Y H:i'));
        $st = $x['phone'] ? send_sms((int)$x['trainer_id'], $x['phone'], $msg) : 'no phone number';
        flash('List confirmed. SMS status for ' . $x['full_name'] . ': ' . $st . '.');
    }
    redirect('director/');
}
$all = db()->query('SELECT up.*, t.full_name FROM uploads up JOIN users t ON t.id=up.trainer_id ORDER BY up.uploaded_at DESC')->fetchAll();
$view = (int)($_GET['view'] ?? 0); $rows = [];
if ($view) { $s = db()->prepare('SELECT * FROM attendance WHERE upload_id=? ORDER BY reg_no, module, session_date LIMIT 200'); $s->execute([$view]); $rows = $s->fetchAll(); }
page_start('Director'); ?>
<p class="row"><a class="btn" href="<?= e(url('director/')) ?>">Attendance lists</a> <a class="btn ghost" href="<?= e(url('director/modules')) ?>">Modules</a></p>
<div class="card"><h2>Attendance lists received</h2>
<?php if (!$all): ?><p class="mute">Nothing uploaded yet.</p><?php else: ?><div class="scroll"><table>
<tr><th>Trainer</th><th>File</th><th>Module(s)</th><th>Rows</th><th>Uploaded</th><th>Status</th><th></th></tr>
<?php foreach ($all as $x): ?><tr><td><?= e($x['full_name']) ?></td><td><?= e($x['file_name']) ?></td><td><?= e($x['modules']) ?></td><td><?= (int)$x['row_count'] ?></td><td><?= e($x['uploaded_at']) ?></td>
<td><span class="tag <?= $x['status'] === 'confirmed' ? 'done' : 'pending' ?>"><?= e(ucfirst($x['status'])) ?></span></td>
<td><div class="row"><a class="btn ghost" href="?view=<?= (int)$x['id'] ?>">View</a>
<?php if ($x['status'] === 'pending'): ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="upload_id" value="<?= (int)$x['id'] ?>"><button>Confirm received</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>
<?php if ($view): ?><div class="card"><h2>List contents <span class="mute">(first 200 rows)</span></h2><div class="scroll"><table>
<tr><th>Reg no</th><th>Name</th><th>Module</th><th>Date</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['reg_no']) ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['module']) ?></td><td><?= e($r['session_date']) ?></td><td><?= $r['present'] ? 'Present' : 'Absent' ?></td></tr><?php endforeach; ?></table></div></div><?php endif; ?>
<?php page_end();

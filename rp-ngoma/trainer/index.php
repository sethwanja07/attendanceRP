<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/parser.php';
$u = require_role('trainer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a CSV or Excel (.xlsx) file first.');
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $rows = normalise_rows(read_table($f['tmp_name'], $ext));
        // Only modules assigned to this trainer by the Director are accepted
        $ms = db()->prepare('SELECT code, name FROM modules WHERE trainer_id = ?'); $ms->execute([$u['id']]);
        $map = [];
        foreach ($ms->fetchAll() as $m) { $map[mb_strtolower($m['name'])] = $m['name']; $map[mb_strtolower($m['code'])] = $m['name']; }
        if (!$map) throw new RuntimeException('No modules are assigned to you yet. Ask the Director of Quality Assurance to assign your modules.');
        $bad = [];
        foreach ($rows as &$r) { $k = mb_strtolower($r[2]); if (isset($map[$k])) $r[2] = $map[$k]; else $bad[$r[2]] = true; }
        unset($r);
        if ($bad) throw new RuntimeException('Not your modules: ' . implode(', ', array_keys($bad)) . '. Your modules: ' . implode(', ', array_unique(array_values($map))) . '. Nothing was saved.');
        $pdo = db(); $pdo->beginTransaction();
        $mods = implode(', ', array_unique(array_column($rows, 2)));
        $pdo->prepare('INSERT INTO uploads (trainer_id,file_name,modules,row_count) VALUES (?,?,?,?)')
            ->execute([$u['id'], basename($f['name']), mb_substr($mods, 0, 250), count($rows)]);
        $uid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO attendance (upload_id,reg_no,student_name,module,session_date,present) VALUES (?,?,?,?,?,?)');
        foreach ($rows as $r) $ins->execute([$uid, $r[0], $r[1], $r[2], $r[3], $r[4]]);
        $pdo->commit();
        flash('Uploaded ' . count($rows) . ' rows. The Director of Quality Assurance can now review the list.');
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        flash($ex->getMessage(), 'err');
    }
    redirect('trainer/');
}
$up = db()->prepare('SELECT * FROM uploads WHERE trainer_id=? ORDER BY uploaded_at DESC'); $up->execute([$u['id']]); $uploads = $up->fetchAll();
$sm = db()->prepare('SELECT * FROM sms_log WHERE user_id=? ORDER BY created_at DESC LIMIT 20'); $sm->execute([$u['id']]); $sms = $sm->fetchAll();
$mm = db()->prepare('SELECT code, name FROM modules WHERE trainer_id = ? ORDER BY name'); $mm->execute([$u['id']]); $mine = $mm->fetchAll();
page_start('Trainer'); ?>
<div class="card"><h2>Upload attendance list</h2>
<p><strong>My modules:</strong> <?= $mine ? e(implode(', ', array_map(fn($m) => $m['name'] . ' (' . $m['code'] . ')', $mine))) : 'none assigned yet' ?></p>
<p class="hint">Accepted: CSV or Excel (.xlsx). Columns: reg_no, student_name, module, date, status (Present/Absent). <a href="<?= e(url('sample_attendance.csv')) ?>" download>Download a sample</a></p>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrf() ?>">
<input type="file" name="file" accept=".csv,.xlsx" required aria-label="Attendance file"><p><button>Upload list</button></p></form></div>

<div class="card"><h2>My uploads</h2>
<?php if (!$uploads): ?><p class="mute">No lists yet.</p><?php else: ?><div class="scroll"><table>
<tr><th>File</th><th>Module(s)</th><th>Rows</th><th>Uploaded</th><th>Status</th></tr>
<?php foreach ($uploads as $x): ?><tr><td><?= e($x['file_name']) ?></td><td><?= e($x['modules']) ?></td><td><?= (int)$x['row_count'] ?></td><td><?= e($x['uploaded_at']) ?></td>
<td><span class="tag <?= $x['status'] === 'confirmed' ? 'done' : 'pending' ?>"><?= $x['status'] === 'confirmed' ? 'Received by QA' : 'Awaiting QA' ?></span></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>

<div class="card"><h2>SMS messages <span class="mute">(<?= e($u['phone']) ?>)</span></h2>
<?php if (!$sms): ?><p class="mute">You will get an SMS when the Director confirms a list.</p><?php endif; ?>
<?php foreach ($sms as $m): ?><div class="sms"><small><?= e($m['created_at']) ?> · <?= e($m['status']) ?></small><?= e($m['message']) ?></div><?php endforeach; ?></div>
<?php page_end();

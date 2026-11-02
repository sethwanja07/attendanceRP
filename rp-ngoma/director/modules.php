<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('director');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $code = strtoupper(trim($_POST['code'] ?? '')); $name = trim($_POST['name'] ?? '');
    $trainer = (int)($_POST['trainer_id'] ?? 0) ?: null;
    try {
        if ($act === 'add' || $act === 'update') {
            if ($code === '' || $name === '') throw new RuntimeException('Enter both a module code and a module name.');
            if ($trainer) { $t = $pdo->prepare('SELECT id FROM users WHERE id=? AND role="trainer"'); $t->execute([$trainer]); if (!$t->fetch()) throw new RuntimeException('Choose a valid trainer.'); }
        }
        if ($act === 'add') {
            $pdo->prepare('INSERT INTO modules (code,name,trainer_id) VALUES (?,?,?)')->execute([$code, $name, $trainer]);
            flash('Module "' . $name . '" added.');
        } elseif ($act === 'update') {
            $old = $pdo->prepare('SELECT name FROM modules WHERE id=?'); $old->execute([(int)$_POST['id']]); $o = $old->fetch();
            if (!$o) throw new RuntimeException('Module not found.');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE modules SET code=?, name=?, trainer_id=? WHERE id=?')->execute([$code, $name, $trainer, (int)$_POST['id']]);
            if ($o['name'] !== $name) $pdo->prepare('UPDATE attendance SET module=? WHERE module=?')->execute([$name, $o['name']]); // keep old records in step
            $pdo->commit();
            flash('Module "' . $name . '" saved.');
        } elseif ($act === 'delete') {
            $m = $pdo->prepare('SELECT name FROM modules WHERE id=?'); $m->execute([(int)$_POST['id']]); $m = $m->fetch();
            if (!$m) throw new RuntimeException('Module not found.');
            $c = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE module=?'); $c->execute([$m['name']]);
            if ($c->fetchColumn() > 0) throw new RuntimeException('"' . $m['name'] . '" already has attendance records, so it cannot be deleted. Unassign the trainer instead.');
            $pdo->prepare('DELETE FROM modules WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Module "' . $m['name'] . '" deleted.');
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = $ex instanceof PDOException && $ex->getCode() === '23000' ? 'That module code or name already exists.' : $ex->getMessage();
        flash($msg, 'err');
    }
    redirect('director/modules');
}
$trainers = $pdo->query('SELECT id, full_name FROM users WHERE role="trainer" ORDER BY full_name')->fetchAll();
$mods = $pdo->query('SELECT * FROM modules ORDER BY name')->fetchAll();
function trainer_select(array $trainers, $sel): string {
    $h = '<select name="trainer_id" aria-label="Trainer"><option value="">Not assigned</option>';
    foreach ($trainers as $t) $h .= '<option value="' . (int)$t['id'] . '"' . ((int)$sel === (int)$t['id'] ? ' selected' : '') . '>' . e($t['full_name']) . '</option>';
    return $h . '</select>';
}
page_start('Modules'); ?>
<p class="row"><a class="btn ghost" href="<?= e(url('director/')) ?>">Attendance lists</a> <a class="btn" href="<?= e(url('director/modules')) ?>">Modules</a></p>
<div class="card"><h2>Add a module</h2>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="add">
<label for="code">Code</label><input id="code" name="code" maxlength="20" placeholder="WDV101" required>
<label for="name">Module name</label><input id="name" name="name" maxlength="100" placeholder="Web Development" required>
<label>Trainer</label><?= trainer_select($trainers, null) ?>
<p><button>Add module</button></p></form></div>

<div class="card"><h2>Modules and trainers</h2>
<p class="hint">Trainers can only upload attendance lists for the modules assigned to them.</p>
<?php if (!$mods): ?><p class="mute">No modules yet. Add the first one above.</p><?php endif; ?>
<?php foreach ($mods as $m): ?>
<form method="post" class="modrow"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
<input name="code" value="<?= e($m['code']) ?>" maxlength="20" aria-label="Code" required>
<input name="name" value="<?= e($m['name']) ?>" maxlength="100" aria-label="Module name" required>
<?= trainer_select($trainers, $m['trainer_id']) ?>
<button name="action" value="update">Save</button>
<button name="action" value="delete" class="ghost" onclick="return confirm('Delete this module?')">Delete</button></form>
<?php endforeach; ?></div>
<?php page_end();

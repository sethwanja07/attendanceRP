<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');
$s = db()->prepare('SELECT a.module, SUM(a.present) AS p, COUNT(*) AS t FROM attendance a JOIN uploads up ON up.id=a.upload_id
                    WHERE a.reg_no=? AND up.status="confirmed" GROUP BY a.module ORDER BY a.module');
$s->execute([$u['reg_no']]); $mods = $s->fetchAll();
$n = count($mods); $sum = 0;
page_start('Student'); ?>
<div class="card"><h2>My attendance marks <span class="mute">(<?= e($u['reg_no']) ?>)</span></h2>
<div class="msg <?= $n >= 2 ? 'ok' : 'err' ?>"><?= $n >= 2 ? "Marks are available in $n modules, which meets the minimum of 2." : "Marks are available in $n module" . ($n === 1 ? '' : 's') . '. You need at least 2 modules with a confirmed attendance list.' ?></div>
<?php if (!$mods): ?><p class="mute">No marks yet. They appear once the Director of Quality Assurance confirms your trainers' lists.</p><?php endif; ?>
<?php foreach ($mods as $m): $pct = round($m['p'] / $m['t'] * 100, 1); $sum += $pct; ?>
<div class="mod"><div><strong><?= e($m['module']) ?></strong><div class="hint">Present <?= (int)$m['p'] ?> of <?= (int)$m['t'] ?> sessions</div></div>
<div class="pct"><?= $pct ?><small>/100</small></div><div class="bar" role="img" aria-label="<?= $pct ?> out of 100"><i style="width:<?= $pct ?>%"></i></div></div>
<?php endforeach; ?>
<?php if ($n): ?><p><strong>Total: <?= round($sum / $n, 1) ?> / 100</strong> <span class="hint">(average across modules)</span></p><?php endif; ?></div>
<?php page_end();

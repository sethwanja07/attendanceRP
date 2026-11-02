<?php
/** Reads an uploaded .csv or .xlsx into rows of [reg_no, student_name, module, date, present]. No Composer needed. */
function read_table(string $path, string $ext): array {
    if ($ext === 'csv') {
        $rows = []; $h = fopen($path, 'r');
        while (($r = fgetcsv($h, 0, ',', '"', '\\')) !== false) $rows[] = $r;
        fclose($h);
        if ($rows && isset($rows[0][0])) $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]); // strip BOM
        return $rows;
    }
    if ($ext === 'xlsx') return read_xlsx($path);
    throw new RuntimeException('Only .csv and .xlsx files are accepted.');
}
function read_xlsx(string $path): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Enable the php zip extension to read .xlsx files, or upload CSV.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new RuntimeException('Cannot open the Excel file.');
    $shared = [];
    if (($x = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        foreach (simplexml_load_string($x)->si as $si) {
            $t = ''; if (isset($si->t)) $t = (string)$si->t; else foreach ($si->r as $r) $t .= (string)$r->t;
            $shared[] = $t;
        }
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
    if ($sheet === false) throw new RuntimeException('Worksheet not found in Excel file.');
    $rows = [];
    foreach (simplexml_load_string($sheet)->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $c) {
            preg_match('/^([A-Z]+)/', (string)$c['r'], $m);
            $col = 0; foreach (str_split($m[1]) as $ch) $col = $col * 26 + ord($ch) - 64;
            $v = (string)$c->v;
            if ((string)$c['t'] === 's') $v = $shared[(int)$v] ?? '';
            elseif ((string)$c['t'] === 'inlineStr') $v = (string)$c->is->t;
            $line[$col - 1] = $v;
        }
        if ($line) { $max = max(array_keys($line)); $out = []; for ($i = 0; $i <= $max; $i++) $out[] = $line[$i] ?? ''; $rows[] = $out; }
    }
    return $rows;
}
function normalise_rows(array $rows): array {
    if (count($rows) < 2) throw new RuntimeException('The file has no data rows.');
    $h = array_map(fn($x) => preg_replace('/[^a-z0-9]/', '', strtolower((string)$x)), $rows[0]);
    $find = function (array $keys) use ($h) { foreach ($h as $i => $v) if (in_array($v, $keys, true)) return $i; return -1; };
    $ix = ['reg' => $find(['regno','reg','regnumber','studentid','id']), 'name' => $find(['name','studentname']),
           'module' => $find(['module','modulename','modulecode']), 'date' => $find(['date','sessiondate']), 'status' => $find(['status','attendance'])];
    $missing = [];
    foreach (['reg' => 'reg_no', 'module' => 'module', 'status' => 'status'] as $k => $label) if ($ix[$k] < 0) $missing[] = $label;
    if ($missing) throw new RuntimeException('Missing column(s): ' . implode(', ', $missing) . '. Expected: reg_no, student_name, module, date, status.');
    $out = [];
    foreach (array_slice($rows, 1) as $r) {
        $reg = trim((string)($r[$ix['reg']] ?? '')); $mod = trim((string)($r[$ix['module']] ?? ''));
        if ($reg === '' || $mod === '') continue;
        $date = $ix['date'] >= 0 ? trim((string)($r[$ix['date']] ?? '')) : '';
        if (is_numeric($date) && $date > 20000) $date = gmdate('Y-m-d', ((int)$date - 25569) * 86400); // Excel serial date
        $ts = $date !== '' ? strtotime($date) : false;
        $out[] = [$reg, trim((string)($r[$ix['name']] ?? '')), $mod, $ts ? date('Y-m-d', $ts) : null,
                  preg_match('/^(p|present|1|yes|y)/i', trim((string)($r[$ix['status']] ?? ''))) ? 1 : 0];
    }
    if (!$out) throw new RuntimeException('No valid rows found.');
    return $out;
}

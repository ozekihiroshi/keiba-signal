<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
array_shift($argv);
if (!$argv) { fwrite(STDERR, "Usage: php scripts/patch_filament_v4_auth_usefix.php <file.php> [...]\n"); exit(1); }

foreach ($argv as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip: $file\n"); continue; }
    $code = file_get_contents($file);
    $orig = $code;

    // 1) クラス名から Base エイリアス名を推定（class Login extends BaseLogin）
    $alias = null;
    if (preg_match('/class\s+(\w+)\s+extends\s+Base(\w+)/', $code, $m)) {
        $alias = $m[2]; // Login など
    }

    // 2) v3 → v4 の Auth 名前空間変更（use Filament\Pages\Auth\X ... → use Filament\Auth\Pages\X ...）
    $code = preg_replace_callback(
        '/^use\s+Filament\\\\Pages\\\\Auth\\\\([A-Za-z\\\\]+)\s+as\s+Base([A-Za-z]+);/m',
        fn($m) => 'use Filament\\Auth\\Pages\\' . $m[1] . ' as Base' . $m[2] . ';',
        $code,
        -1,
        $n1
    );
    $code = preg_replace_callback(
        '/^use\s+Filament\\\\Pages\\\\Auth\\\\([A-Za-z\\\\]+);/m',
        fn($m) => 'use Filament\\Auth\\Pages\\' . $m[1] . ';',
        $code,
        -1,
        $n2
    );

    // 3) 置換事故の残骸: use Filament\Auth\Pages$1 as BaseLogin; を復元
    if ($alias && preg_match('/^use\s+Filament\\\\Auth\\\\Pages\$\d+\s+as\s+Base[A-Za-z]+;$/m', $code)) {
        $code = preg_replace(
            '/^use\s+Filament\\\\Auth\\\\Pages\$\d+\s+as\s+Base[A-Za-z]+;$/m',
            'use Filament\\Auth\\Pages\\' . $alias . ' as Base' . $alias . ';',
            $code,
            1
        );
    }

    // 4) use Filament\Forms\Form; → use Filament\Schemas\Schema;
    $code = preg_replace('/^use\s+Filament\\\\Forms\\\\Form;[ \t]*$/m', 'use Filament\\Schemas\\Schema;', $code, -1, $u1);
    // なければ追記
    if (!preg_match('/^use\s+Filament\\\\Schemas\\\\Schema;[ \t]*$/m', $code)) {
        if (preg_match('/^(namespace\s+[^\r\n;]+;\s*(?:\Ruse\s+[^\r\n;]+;)+)/m', $code, $m2, PREG_OFFSET_CAPTURE)) {
            $p = $m2[1][1] + strlen($m2[1][0]);
            $code = substr($code, 0, $p) . "\nuse Filament\\Schemas\\Schema;" . substr($code, $p);
        }
    }

    // 5) form() シグネチャを Schema へ（static/instance 両対応）
    $code = preg_replace('/(public\s+static\s+function\s+form\s*\()\s*Form\s+\$form\s*(\)\s*:\s*)Form/i', '$1Schema $schema$2Schema', $code, -1, $s1);
    $code = preg_replace('/(public\s+function\s+form\s*\()\s*Form\s+\$form\s*(\)\s*:\s*)Form/i', '$1Schema $schema$2Schema', $code, -1, $s2);
    $code = preg_replace('/(public\s+function\s+form\s*\()\s*Form\s+\$form\s*(\))/i', '$1Schema $schema$2: Schema', $code, -1, $s3);
    $code = preg_replace('/(public\s+static\s+function\s+form\s*\()\s*Form\s+\$form\s*(\))/i', '$1Schema $schema$2: Schema', $code, -1, $s4);

    // 6) form() 本文の $form->schema( → $schema->components(、$form → $schema（メソッドブロック限定）
    $pos = 0;
    while (preg_match('/public\s+(?:static\s+)?function\s+form\s*\(\s*Schema\s+\$schema[^\{]*\{/i', $code, $m3, PREG_OFFSET_CAPTURE, $pos)) {
        $brace = strpos($code, '{', $m3[0][1]); if ($brace === false) break;
        $depth = 0; $i = $brace; $len = strlen($code);
        for (; $i < $len; $i++) { $ch = $code[$i]; if ($ch === '{') $depth++; elseif ($ch === '}') { $depth--; if ($depth === 0) break; } }
        if ($depth !== 0) break;
        $bs = $brace + 1; $be = $i;
        $body = substr($code, $bs, $be - $bs);
        $body = preg_replace('/\$form\s*->\s*schema\s*\(/', '$schema->components(', $body);
        $body = preg_replace('/\$(form)\b/', '$schema', $body);
        $code = substr($code, 0, $bs) . $body . substr($code, $be);
        $pos = $be + 1;
    }

    if ($code !== $orig) { file_put_contents($file, $code); echo "patched: $file\n"; }
    else { echo "no change: $file\n"; }
}

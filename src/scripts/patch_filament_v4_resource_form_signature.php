<?php
declare(strict_types=1);

// 最小・安全実装：DocBlockなし。CLI専用。
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

array_shift($argv);
if (!$argv) {
    fwrite(STDERR, "Usage: php scripts/patch_filament_v4_resource_form_signature.php <file.php> [...]\n");
    exit(1);
}

foreach ($argv as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip (not found): $file\n"); continue; }

    $code = file_get_contents($file);
    $orig = $code;

    // 1) use の置換: Forms\Form -> Schemas\Schema
    $code = preg_replace(
        '/^use\s+Filament\\\\Forms\\\\Form;[ \t]*$/m',
        'use Filament\\Schemas\\Schema;',
        $code,
        -1,
        $cUse
    );

    // 1b) もし Schema の use が無ければ namespace/use 群の末尾に追記
    if (!preg_match('/^use\s+Filament\\\\Schemas\\\\Schema;[ \t]*$/m', $code)) {
        if (preg_match('/^namespace\s+[^\r\n;]+;\R((?:use\s+[^\r\n;]+;\R)+)/m', $code, $m, PREG_OFFSET_CAPTURE)) {
            $insertPos = $m[1][1] + strlen($m[1][0]);
            $code = substr($code, 0, $insertPos) . "use Filament\\Schemas\\Schema;\n" . substr($code, $insertPos);
        }
    }

    // 2) シグネチャ置換: form(Form $form): Form → form(Schema $schema): Schema（戻り型省略ケースも拾う）
    $code = preg_replace(
        '/public\s+static\s+function\s+form\s*\(\s*Form\s+\$form\s*\)\s*:\s*Form/i',
        'public static function form(Schema $schema): Schema',
        $code,
        -1,
        $cSig1
    );
    $code = preg_replace(
        '/public\s+static\s+function\s+form\s*\(\s*Form\s+\$form\s*\)/i',
        'public static function form(Schema $schema): Schema',
        $code,
        -1,
        $cSig2
    );

    // 3) form(...) 本文だけを編集：$form->schema( → $schema->components(、$form → $schema
    $pos = 0;
    while (preg_match('/public\s+static\s+function\s+form\s*\(\s*Schema\s+\$schema[^\{]*\{/i', $code, $m, PREG_OFFSET_CAPTURE, $pos)) {
        $brace = strpos($code, '{', $m[0][1]);
        if ($brace === false) break;

        $depth = 0; $i = $brace; $len = strlen($code);
        for (; $i < $len; $i++) {
            $ch = $code[$i];
            if ($ch === '{') $depth++;
            elseif ($ch === '}') { $depth--; if ($depth === 0) break; }
        }
        if ($depth !== 0) break;

        $bodyStart = $brace + 1; $bodyEnd = $i;
        $body = substr($code, $bodyStart, $bodyEnd - $bodyStart);

        $body = preg_replace('/\$form\s*->\s*schema\s*\(/', '$schema->components(', $body, -1, $cA);
        $body = preg_replace('/\$(form)\b/', '$schema', $body, -1, $cB);

        $code = substr($code, 0, $bodyStart) . $body . substr($code, $bodyEnd);
        $pos = $bodyEnd + 1;
    }

    if ($code !== $orig) { file_put_contents($file, $code); echo "patched: $file\n"; }
    else { echo "no change: $file\n"; }
}

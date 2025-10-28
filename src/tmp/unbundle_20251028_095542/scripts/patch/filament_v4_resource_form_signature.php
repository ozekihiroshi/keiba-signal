<?php
declare(strict_types=1);

if ($argc < 2) { fwrite(STDERR, "Usage: php {$argv[0]} <file.php> [...]\n"); exit(1); }
array_shift($argv);

foreach ($argv as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip (not file): $file\n"); continue; }

    $s = file_get_contents($file);

    // 1) remove legacy use Filament\Forms\Form;
    $s = preg_replace('/^use\s+Filament\\\\Forms\\\\Form;\s*$/m', '', $s, -1);

    // 2) ensure use Filament\Schemas\Schema;
    if (!preg_match('/^use\s+Filament\\\\Schemas\\\\Schema;\s*$/m', $s)) {
        $s = preg_replace('/^(namespace[^\n]+\n)/', "$1use Filament\\Schemas\\Schema;\n", $s, 1);
    }

    // 3) signature: form(Form $form): Form  → form(Schema $schema): Schema
    $s2 = preg_replace(
        '/public\s+static\s+function\s+form\(\s*(?:\\\\?Filament\\\\Forms\\\\)?Form\s+\$(\w+)\s*\)\s*:\s*(?:\\\\?Filament\\\\Forms\\\\)?Form/',
        'public static function form(Schema $$1): Schema',
        $s,
        1,
        $changed
    );

    // 4) rename variable $form → $schema（素朴置換だが一般的に安全）
    if ($changed) {
        $s2 = str_replace('$form', '$schema', $s2);
    } else {
        $s2 = $s;
    }

    if ($s2 !== $s) {
        file_put_contents($file, $s2);
        echo "patched form(): $file\n";
    } else {
        echo "no change (form): $file\n";
    }
}

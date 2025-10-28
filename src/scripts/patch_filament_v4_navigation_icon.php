<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }

array_shift($argv);
if (!$argv) {
    fwrite(STDERR, "Usage: php scripts/patch_filament_v4_navigation_icon.php <file.php> [...]\n");
    exit(1);
}

foreach ($argv as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip (not found): $file\n"); continue; }

    $code = file_get_contents($file);
    $orig = $code;

    // `protected static ?string $navigationIcon [= ...];`
    // → `protected static \BackedEnum|string|null $navigationIcon [= ...];`
    $code = preg_replace(
        '/(protected\s+static\s+)\?string(\s+\$navigationIcon\b)(\s*=\s*[^;]*?)?;/',
        '$1\\\BackedEnum|string|null$2$3;',
        $code,
        -1,
        $count
    );

    if ($code !== $orig) {
        file_put_contents($file, $code);
        echo "patched: $file ($count)\n";
    } else {
        echo "no change: $file\n";
    }
}

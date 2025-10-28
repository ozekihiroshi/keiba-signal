<?php
declare(strict_types=1);

if ($argc < 2) { fwrite(STDERR, "Usage: php {$argv[0]} <file.php> [...]\n"); exit(1); }
array_shift($argv);

foreach ($argv as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip (not file): $file\n"); continue; }

    $src = file_get_contents($file);
    $pat = '/(protected\s+static\s+)\?string(\s+\$navigationIcon\b)/';
    $rep = '$1\\BackedEnum|string|null$2';
    $out = preg_replace($pat, $rep, $src, -1, $c);

    if ($c) {
        file_put_contents($file, $out);
        echo "patched navigationIcon: $file\n";
    } else {
        echo "no change (navigationIcon): $file\n";
    }
}

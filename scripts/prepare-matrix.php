<?php

// Create an isolated Composer project, keeping the main vendor directory intact.
$major = $argv[1] ?? '';
if (!in_array($major, ['7', '8', '9', '10', '11', '12', '13'], true)) {
    fwrite(STDERR, "Usage: php scripts/prepare-matrix.php <Laravel major 7..13>\n");
    exit(1);
}
$testbench = (int) $major - 2;
$phpunit = (int) $major <= 10 ? '^9.6' : ((int) $major <= 12 ? '^11.5' : '^12.0');
$directory = __DIR__.'/../.compat/laravel'.$major;
@mkdir($directory, 0755, true);
$manifest = [
    'name' => 'tablekit/compatibility-test',
    'type' => 'project',
    'require' => [
        'thuraaung/tablekit' => '@dev',
        'laravel/framework' => '^'.$major.'.0',
        'orchestra/testbench' => '^'.$testbench.'.0',
        'phpunit/phpunit' => $phpunit,
    ],
    'repositories' => [[
        'type' => 'path',
        'url' => '../..',
        'options' => ['symlink' => true],
    ]],
    'autoload-dev' => ['psr-4' => ['Thuraaung\\TableKit\\Tests\\' => '../../tests/']],
    'config' => ['allow-plugins' => false],
];
file_put_contents($directory.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo $directory."\n";

<?php

$root = dirname(__DIR__);
$demo = $root.'/demo';
@mkdir($demo.'/bootstrap/cache', 0755, true);
@mkdir($demo.'/database', 0755, true);
if (!is_file($demo.'/.env')) {
    file_put_contents($demo.'/.env', 'APP_KEY=base64:'.base64_encode(random_bytes(32))."\nAPP_ENV=local\n");
}
if (!is_file($demo.'/database/demo.sqlite')) {
    touch($demo.'/database/demo.sqlite');
}
@mkdir($demo.'/public/engines', 0755, true);
$assets = [
    'node_modules/jquery/dist/jquery.js' => 'jquery.js',
    'node_modules/dt110/js/jquery.dataTables.js' => 'dt110.js',
    'node_modules/dt113/js/jquery.dataTables.js' => 'dt113.js',
    'node_modules/dt2/js/dataTables.js' => 'dt2.js',
    'node_modules/dt3/js/dataTables.js' => 'dt3.js',
];
foreach ($assets as $source => $destination) {
    if (!is_file($root.'/'.$source)) {
        fwrite(STDERR, "Run npm ci --ignore-scripts before setting up demo assets.\n");
        exit(1);
    }
    copy($root.'/'.$source, $demo.'/public/engines/'.$destination);
}
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($demo.'/artisan').' vendor:publish --tag=tablekit-assets --force', $status);
if ($status !== 0) {
    exit($status);
}
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($demo.'/artisan').' demo:seed', $status);
exit($status);

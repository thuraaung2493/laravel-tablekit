<?php

$vendor = getenv('TABLEKIT_VENDOR') ?: (is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/../vendor' : __DIR__.'/../../vendor');
require_once $vendor.'/autoload.php';
spl_autoload_register(function ($class) {
    $prefix = 'TableKitDemo\\';
    if (strpos($class, $prefix) === 0) {
        $file = __DIR__.'/../app/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $path) {
    if (!is_dir(__DIR__.'/../storage/'.$path)) {
        mkdir(__DIR__.'/../storage/'.$path, 0755, true);
    }
}
$app = new Illuminate\Foundation\Application(realpath(__DIR__.'/..'));
$app->singleton(Illuminate\Contracts\Http\Kernel::class, TableKitDemo\Http\Kernel::class);
$app->singleton(Illuminate\Contracts\Console\Kernel::class, TableKitDemo\Console\Kernel::class);
$app->singleton(Illuminate\Contracts\Debug\ExceptionHandler::class, Illuminate\Foundation\Exceptions\Handler::class);

return $app;

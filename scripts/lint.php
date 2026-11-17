<?php

$directories = ['src', 'config', 'tests', 'resources/stubs', 'demo/app', 'demo/routes', 'demo/config', 'scripts'];
$failed = false;
foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($files as $file) {
        if (!$file->isFile() || !preg_match('/\.(php|stub)$/', $file->getFilename())) {
            continue;
        }
        $path = $file->getPathname();
        $temporary = null;
        if ($file->getExtension() === 'stub') {
            $temporary = tempnam(sys_get_temp_dir(), 'tablekit-stub-');
            $source = str_replace(
                ['{{namespace}}', '{{class}}', '{{model}}'],
                ['App\\DataTables', 'ExampleTable', 'App\\Models\\Client'],
                file_get_contents($path)
            );
            file_put_contents($temporary, $source);
            $path = $temporary;
        }
        passthru(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path), $status);
        if ($temporary !== null) {
            unlink($temporary);
        }
        $failed = $failed || $status !== 0;
    }
}
exit($failed ? 1 : 0);

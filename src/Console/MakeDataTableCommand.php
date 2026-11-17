<?php

namespace Thuraaung\TableKit\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeDataTableCommand extends Command
{
    protected $signature = 'tablekit:make {name : Table class, optionally namespaced} {--model= : Model class} {--force : Overwrite an existing table}';
    protected $description = 'Create a small, server-side TableKit DataTable class';

    public function handle(Filesystem $files): int
    {
        $name = str_replace('/', '\\', $this->argument('name'));
        $model = ltrim((string) ($this->option('model') ?: Str::singular(preg_replace('/Table$/', '', class_basename($name)))), '\\');
        $pattern = '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D';
        if (!preg_match($pattern, $name) || !preg_match($pattern, $model)) {
            $this->error('Use valid PHP class names without paths or traversal.');

            return 1;
        }
        $root = rtrim($this->laravel->getNamespace(), '\\');
        if (strpos($model, '\\') === false) {
            $legacy = $root.'\\'.$model;
            $model = class_exists($legacy) ? $legacy : $root.'\\Models\\'.$model;
        }
        $parts = explode('\\', $name);
        $class = array_pop($parts);
        $namespace = $root.'\\DataTables'.($parts ? '\\'.implode('\\', $parts) : '');
        $path = app_path('DataTables/'.str_replace('\\', '/', $name).'.php');
        if ($files->exists($path) && !$this->option('force')) {
            $this->error('The table already exists. Use --force to overwrite it.');

            return 1;
        }
        $stub = $files->get(__DIR__.'/../../resources/stubs/table.stub');
        $stub = str_replace(['{{namespace}}', '{{class}}', '{{model}}'], [$namespace, $class, $model], $stub);
        try {
            token_get_all($stub, TOKEN_PARSE);
        } catch (\ParseError $error) {
            $this->error('The class or model name is not valid PHP.');

            return 1;
        }
        $files->makeDirectory(dirname($path), 0755, true, true);
        $files->put($path, $stub);
        $this->info('Created '.$path);

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'make:service {name}';
    protected $description = 'Create a new service class';
    public function handle()
    {
        $name = $this->argument('name');
        $serviceName = $name . 'Service';
        $path = app_path("Services/{$serviceName}.php");



        if (File::exists($path)) {
            $this->error("Service {$serviceName} already exists.");
            return;
        }
        $newServiceName = class_basename($serviceName);
        $parts = explode('/', $serviceName);
        $prefix = $parts[0];
        File::ensureDirectoryExists(app_path('Services'));

        $stub = <<<PHP
<?php

namespace App\Services\\{$prefix};

class {$newServiceName}
{
    //
}

PHP;

        File::put($path, $stub);

        $this->info("Service created successfully.");
    }
}

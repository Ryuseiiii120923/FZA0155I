<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRepositories extends Command
{
    protected $signature = 'make:repository {name} {--s}';
    protected $description = 'Create a repository class';

    public function handle()
    {
        $name = $this->argument('name');

        $repositoryName = str_ends_with($name, 'Repository')
            ? $name
            : $name . 'Repository';

        $repositoryPath = app_path("Repositories/{$repositoryName}.php");

        File::ensureDirectoryExists(dirname($repositoryPath));
        $newrepositoryName = class_basename($repositoryName);
        $parts = explode('/', $repositoryName);
        $prefix = $parts[0];

        File::put(
            $repositoryPath,
            <<<PHP
<?php

namespace App\Repositories\\{$prefix};

class {$newrepositoryName}
{
    //
}

PHP
        );

        $this->info("Repository [{$repositoryName}] created.");

        if ($this->option('s')) {
            $serviceName = str_replace('Repository', 'Service', $repositoryName);

            $servicePath = app_path("Services/{$serviceName}.php");

            File::ensureDirectoryExists(dirname($servicePath));
            $newserviceName = class_basename($serviceName);
            $Serviceprefix = Str::before($serviceName, '\\');
            File::put(
                $servicePath,
                <<<PHP
<?php

namespace App\Services;

use App\Repositories\\{$prefix}\\{$newrepositoryName};

class {$newserviceName}
{
    protected \$repo;
    public function __construct(protected {$newrepositoryName} \$repository) 
    {
    \$this->repo = \$repository;
    }
}

PHP
            );

            $this->info("Service [{$serviceName}] created.");
        }

        return self::SUCCESS;
    }
}
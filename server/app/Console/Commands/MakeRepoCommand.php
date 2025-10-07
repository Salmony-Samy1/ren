<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRepoCommand extends Command
{
    protected $signature = 'make:repo {name}';
    protected $description = 'Generate model, repo class + interface and register it automatically';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $modelName = $name;
        $folderName = "{$name}Repo";
        $interfaceName = "I{$name}Repo";
        $repoName = "{$name}Repo";

        $modelPath = app_path("Models/{$modelName}.php");
        $repoDir = app_path("Repositories/{$folderName}");
        $interfacePath = "{$repoDir}/{$interfaceName}.php";
        $repoPath = "{$repoDir}/{$repoName}.php";

        // Create model if not exists
        if (!File::exists($modelPath)) {
            $this->call('make:model', ['name' => "Models/{$modelName}"]);
            $this->info("✅ Model created: App\\Models\\{$modelName}");
        }

        File::ensureDirectoryExists($repoDir);

        // Create interface
        if (!File::exists($interfacePath)) {
            File::put($interfacePath, $this->getInterfaceStub($name));
            $this->info("✅ Interface created: {$interfaceName}.php");
        }

        // Create repository class
        if (!File::exists($repoPath)) {
            File::put($repoPath, $this->getRepoStub($name));
            $this->info("✅ Repository created: {$repoName}.php");
        }

        // Register binding
        $this->registerInServiceProvider($name);

        $this->info("🎉 Done!");
        return self::SUCCESS;
    }

    protected function getInterfaceStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\Repositories\\{$name}Repo;

use App\Repositories\IBaseRepo;

interface I{$name}Repo extends IBaseRepo
{
    // Define {$name} specific methods
}
PHP;
    }

    protected function getRepoStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\Repositories\\{$name}Repo;

use App\Models\\{$name};
use App\Repositories\BaseRepo;

class {$name}Repo extends BaseRepo implements I{$name}Repo
{
    public function __construct()
    {
        \$this->model = {$name}::class;
    }
}
PHP;
    }

    protected function registerInServiceProvider(string $name): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        $interface = "\\App\\Repositories\\{$name}Repo\\I{$name}Repo";
        $repo = "\\App\\Repositories\\{$name}Repo\\{$name}Repo";

        if (!File::exists($providerPath)) {
        File::ensureDirectoryExists(app_path('Providers'));
        File::put($providerPath, $this->getProviderStub());
            $this->info("✅ Created RepositoryServiceProvider");
        }

        $providerContent = File::get($providerPath);

        $bindingCode = "\$this->app->bind({$interface}::class, {$repo}::class);";

        if (!Str::contains($providerContent, $bindingCode)) {
            $providerContent = str_replace(
                '// bindings',
                "        {$bindingCode}\n        // bindings",
                $providerContent
            );

            File::put($providerPath, $providerContent);
            $this->info("🔁 Registered in RepositoryServiceProvider");
        } else {
            $this->warn("⚠️ Already registered in RepositoryServiceProvider");
        }
    }

    protected function getProviderStub(): string
    {
        return <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // bindings
    }

    public function boot(): void {}
}
PHP;
    }
}

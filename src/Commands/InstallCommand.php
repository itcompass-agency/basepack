<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'basepack:install 
                            {--dev : Install development environment}
                            {--prod : Install production environment}
                            {--force : Overwrite existing files}';

    protected $description = 'Install BasePack DevOps toolkit';

    public function handle(): int
    {
        $this->info('Installing BasePack DevOps toolkit...');

        $environment = $this->option('dev') ? 'dev' : ($this->option('prod') ? 'prod' : null);
        
        if (!$environment) {
            $environment = $this->choice(
                'Which environment would you like to install?',
                ['dev', 'prod', 'both'],
                'dev'
            );
        }

        $force = $this->option('force');

        // Копируем Docker файлы
        $this->publishDockerFiles($force);
        
        // Копируем Makefile
        $this->publishMakefile($force);
        
        // Копируем docker-compose файлы
        $this->publishDockerCompose($environment, $force);
        
        // Создаем .env.docker если не существует
        $this->createDockerEnv($force);
        
        // Обновляем .gitignore
        $this->updateGitignore();

        $this->info('BasePack installed successfully!');
        $this->newLine();
        
        $this->table(
            ['Next Steps'],
            [
                ['1. Review and update .env file with your database credentials'],
                ['2. Run: make build'],
                ['3. Run: make start'],
                ['4. Run: make composer-install'],
                ['5. Run: make migrate'],
            ]
        );

        return Command::SUCCESS;
    }

    protected function publishDockerFiles(bool $force): void
    {
        $source = __DIR__.'/../../stubs/docker';
        $destination = base_path('.docker');

        if (File::exists($destination) && !$force) {
            if (!$this->confirm('.docker directory already exists. Do you want to overwrite it?')) {
                return;
            }
        }

        File::copyDirectory($source, $destination);
        $this->info('Docker files published successfully.');
    }

    protected function publishMakefile(bool $force): void
    {
        $source = __DIR__.'/../../stubs/Makefile.stub';
        $destination = base_path('Makefile');

        if (File::exists($destination) && !$force) {
            if (!$this->confirm('Makefile already exists. Do you want to overwrite it?')) {
                return;
            }
        }

        // Заменяем плейсхолдеры в Makefile
        $content = File::get($source);
        $content = str_replace(
            ['{{PROJECT_NAME}}', '{{APP_NAME}}'],
            [
                strtolower(str_replace(' ', '-', config('app.name', 'laravel'))),
                config('app.name', 'Laravel')
            ],
            $content
        );

        File::put($destination, $content);
        $this->info('Makefile published successfully.');
    }

    protected function publishDockerCompose(string $environment, bool $force): void
    {
        $files = [];
        
        if ($environment === 'dev' || $environment === 'both') {
            $files['docker-compose.yml.stub'] = 'docker-compose.yml';
        }
        
        if ($environment === 'prod' || $environment === 'both') {
            $files['docker-compose-prod.yml.stub'] = 'docker-compose-prod.yml';
        }

        foreach ($files as $source => $destination) {
            $sourcePath = __DIR__.'/../../stubs/'.$source;
            $destinationPath = base_path($destination);

            if (File::exists($destinationPath) && !$force) {
                if (!$this->confirm("$destination already exists. Do you want to overwrite it?")) {
                    continue;
                }
            }

            $content = File::get($sourcePath);
            // Заменяем плейсхолдеры
            $content = str_replace(
                '{{PROJECT_NAME}}',
                strtolower(str_replace(' ', '-', config('app.name', 'laravel'))),
                $content
            );

            File::put($destinationPath, $content);
            $this->info("$destination published successfully.");
        }
    }

    protected function createDockerEnv(bool $force): void
    {
        $envExample = base_path('.env.example');
        $envDocker = base_path('.env.docker');
        
        if (File::exists($envDocker) && !$force) {
            return;
        }

        // Копируем .env.example или создаем базовый .env для Docker
        if (File::exists($envExample)) {
            File::copy($envExample, $envDocker);
        } else {
            $defaultEnv = File::get(__DIR__.'/../../stubs/.env.docker.stub');
            File::put($envDocker, $defaultEnv);
        }

        // Обновляем значения для Docker окружения
        $this->updateEnvFile($envDocker);
        
        $this->info('.env.docker file created successfully.');
    }

    protected function updateEnvFile(string $path): void
    {
        $env = File::get($path);
        
        $replacements = [
            'DB_HOST=127.0.0.1' => 'DB_HOST=mysql',
            'REDIS_HOST=127.0.0.1' => 'REDIS_HOST=redis',
            'CACHE_DRIVER=file' => 'CACHE_DRIVER=redis',
            'SESSION_DRIVER=file' => 'SESSION_DRIVER=redis',
            'QUEUE_CONNECTION=sync' => 'QUEUE_CONNECTION=redis',
        ];

        foreach ($replacements as $search => $replace) {
            $env = preg_replace('/^'.preg_quote($search, '/').'$/m', $replace, $env);
        }

        File::put($path, $env);
    }

    protected function updateGitignore(): void
    {
        $gitignore = base_path('.gitignore');
        
        if (!File::exists($gitignore)) {
            return;
        }

        $content = File::get($gitignore);
        $toAdd = [
            '.docker/general/ssl/*.pem',
            '.docker/general/ssl/*.key',
            '.env.docker',
        ];

        foreach ($toAdd as $line) {
            if (!str_contains($content, $line)) {
                $content .= "\n" . $line;
            }
        }

        File::put($gitignore, $content);
        $this->info('.gitignore updated successfully.');
    }
}
<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiagnoseCommand extends Command
{
    protected $signature = 'basepack:diagnose 
                            {--fix : Attempt to fix common issues}';

    protected $description = 'Diagnose BasePack installation and Docker structure';

    protected $errors = [];
    protected $warnings = [];
    protected $success = [];

    public function handle(): int
    {
        $this->info('🔍 Diagnosing BasePack installation...');
        $this->newLine();

        $this->checkDockerStructure();
        $this->checkEnvironmentFiles();
        $this->checkSSLCertificates();
        $this->checkDockerCompose();
        $this->checkMakefile();

        if ($this->option('fix')) {
            $this->attemptFixes();
        }

        $this->displayResults();

        return empty($this->errors) ? Command::SUCCESS : Command::FAILURE;
    }

    protected function checkDockerStructure(): void
    {
        $requiredFiles = [
            '.docker/Dockerfile' => 'Main Laravel Dockerfile',
            '.docker/nginx/Dockerfile' => 'Nginx Dockerfile',
            '.docker/dev/nginx.conf' => 'Dev Nginx config',
            '.docker/prod/nginx.conf' => 'Prod Nginx config',
            '.docker/dev/php.ini' => 'Dev PHP config',
            '.docker/prod/php.ini' => 'Prod PHP config',
            '.docker/dev/www.conf' => 'Dev PHP-FPM config',
            '.docker/prod/www.conf' => 'Prod PHP-FPM config',
            '.docker/dev/xdebug-main.ini' => 'Xdebug main config',
            '.docker/dev/xdebug-osx.ini' => 'Xdebug OSX config',
            '.docker/dev/init-db.sql' => 'Database init script',
            '.docker/general/do_we_need_xdebug.sh' => 'Xdebug installer script',
            '.docker/general/supervisord.conf' => 'Supervisor config',
            '.docker/general/cron' => 'Cron config',
        ];

        foreach ($requiredFiles as $path => $description) {
            if (File::exists(base_path($path))) {
                $this->success[] = "✅ {$description}: {$path}";
            } else {
                $this->errors[] = "❌ Missing: {$description} ({$path})";
            }
        }

        // Check SSL certificates separately
        $sslPath = base_path('.docker/general/ssl');
        if (File::exists($sslPath)) {
            if (File::exists($sslPath . '/cert.pem') && File::exists($sslPath . '/key.pem')) {
                $this->success[] = "✅ SSL certificates found in Docker directory";
            } else {
                $this->warnings[] = "⚠️  SSL directory exists but certificates missing";
            }
        } else {
            $this->errors[] = "❌ SSL directory missing: .docker/general/ssl/";
        }
    }

    protected function checkEnvironmentFiles(): void
    {
        $envFiles = [
            '.env' => 'Main environment file',
            '.env.docker' => 'Docker environment template',
        ];

        foreach ($envFiles as $file => $description) {
            $path = base_path($file);
            if (File::exists($path)) {
                $content = File::get($path);
                
                // Check for required variables
                $requiredVars = [
                    'DB_HOST',
                    'DB_PORT',
                    'DB_DATABASE',
                    'DB_USERNAME',
                    'DB_PASSWORD',
                    'DB_OUTER_PORT',
                    'REDIS_HOST',
                    'REDIS_OUTER_PORT',
                    'WEB_PORT_HTTP',
                    'WEB_PORT_SSL',
                ];

                $missingVars = [];
                foreach ($requiredVars as $var) {
                    if (!preg_match("/^{$var}=/m", $content)) {
                        $missingVars[] = $var;
                    }
                }

                if (empty($missingVars)) {
                    $this->success[] = "✅ {$description}: All required variables present";
                } else {
                    $this->warnings[] = "⚠️  {$description}: Missing variables: " . implode(', ', $missingVars);
                }
            } else {
                $this->errors[] = "❌ Missing: {$description} ({$file})";
            }
        }
    }

    protected function checkSSLCertificates(): void
    {
        $sslPaths = [
            '.docker/general/ssl',
            '.ssl',
            'ssl',
            'certificates',
        ];

        $found = false;
        foreach ($sslPaths as $path) {
            $fullPath = base_path($path);
            if (File::exists($fullPath . '/cert.pem') && File::exists($fullPath . '/key.pem')) {
                $this->success[] = "✅ SSL certificates found in: {$path}/";
                $found = true;
            }
        }

        if (!$found) {
            $this->errors[] = "❌ No SSL certificates found in any standard location";
        }
    }

    protected function checkDockerCompose(): void
    {
        $composeFiles = [
            'docker-compose.yml' => 'Development compose file',
            'docker-compose-prod.yml' => 'Production compose file',
        ];

        foreach ($composeFiles as $file => $description) {
            if (File::exists(base_path($file))) {
                $this->success[] = "✅ {$description} exists";
            } else {
                $this->warnings[] = "⚠️  Missing: {$description}";
            }
        }
    }

    protected function checkMakefile(): void
    {
        if (File::exists(base_path('Makefile'))) {
            $this->success[] = "✅ Makefile exists";
        } else {
            $this->errors[] = "❌ Missing: Makefile";
        }
    }

    protected function attemptFixes(): void
    {
        $this->info('🔧 Attempting to fix issues...');
        $this->newLine();

        // Fix missing .env
        if (!File::exists(base_path('.env'))) {
            if (File::exists(base_path('.env.docker'))) {
                File::copy(base_path('.env.docker'), base_path('.env'));
                $this->info('Fixed: Created .env from .env.docker');
            } elseif (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->updateEnvForDocker(base_path('.env'));
                $this->info('Fixed: Created .env from .env.example');
            }
        }

        // Fix missing environment variables
        $this->fixEnvironmentVariables();

        // Fix SSL directory
        if (!File::exists(base_path('.docker/general/ssl'))) {
            File::makeDirectory(base_path('.docker/general/ssl'), 0755, true);
            $this->info('Fixed: Created SSL directory');

            // Copy SSL from other locations if available
            $sslSources = ['.ssl', 'ssl', 'certificates'];
            foreach ($sslSources as $source) {
                $sourcePath = base_path($source);
                if (File::exists($sourcePath . '/cert.pem') && File::exists($sourcePath . '/key.pem')) {
                    File::copy($sourcePath . '/cert.pem', base_path('.docker/general/ssl/cert.pem'));
                    File::copy($sourcePath . '/key.pem', base_path('.docker/general/ssl/key.pem'));
                    $this->info("Fixed: Copied SSL certificates from {$source}/");
                    break;
                }
            }
        }
    }

    protected function fixEnvironmentVariables(): void
    {
        if (!File::exists(base_path('.env'))) {
            return;
        }

        $env = File::get(base_path('.env'));
        $updated = false;

        $defaults = [
            'DB_HOST' => 'mysql',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'laravel',
            'DB_USERNAME' => 'laravel',
            'DB_PASSWORD' => 'secret',
            'DB_OUTER_PORT' => '3306',
            'REDIS_HOST' => 'redis',
            'REDIS_PORT' => '6379',
            'REDIS_OUTER_PORT' => '6379',
            'WEB_PORT_HTTP' => '80',
            'WEB_PORT_SSL' => '443',
            'XDEBUG_CONFIG' => 'main',
            'INNODB_USE_NATIVE_AIO' => '1',
        ];

        foreach ($defaults as $key => $value) {
            if (!preg_match("/^{$key}=/m", $env)) {
                $env .= "\n{$key}={$value}";
                $updated = true;
            }
        }

        if ($updated) {
            File::put(base_path('.env'), $env);
            $this->info('Fixed: Added missing environment variables');
        }
    }

    protected function updateEnvForDocker(string $path): void
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

    protected function displayResults(): void
    {
        $this->newLine();
        
        if (!empty($this->success)) {
            $this->info('✅ Successful checks:');
            foreach ($this->success as $message) {
                $this->line("  {$message}");
            }
            $this->newLine();
        }

        if (!empty($this->warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($this->warnings as $message) {
                $this->line("  {$message}");
            }
            $this->newLine();
        }

        if (!empty($this->errors)) {
            $this->error('❌ Errors:');
            foreach ($this->errors as $message) {
                $this->line("  {$message}");
            }
            $this->newLine();
        }

        // Summary
        $this->line('📊 Summary:');
        $this->line("  ✅ Success: " . count($this->success));
        $this->line("  ⚠️  Warnings: " . count($this->warnings));
        $this->line("  ❌ Errors: " . count($this->errors));

        if (empty($this->errors)) {
            $this->newLine();
            $this->info('🎉 BasePack installation looks good!');
            
            if (!empty($this->warnings)) {
                $this->info('Consider addressing the warnings for optimal performance.');
            }
        } else {
            $this->newLine();
            $this->error('⚠️  Issues found! Run with --fix flag to attempt automatic fixes:');
            $this->line('  php artisan basepack:diagnose --fix');
        }
    }
}
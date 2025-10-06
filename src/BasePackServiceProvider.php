<?php

namespace ITCompass\BasePack;

use Illuminate\Support\ServiceProvider;
use ITCompass\BasePack\Commands\InstallCommand;
use ITCompass\BasePack\Commands\BuildCommand;
use ITCompass\BasePack\Commands\PublishCommand;
use ITCompass\BasePack\Commands\StatusCommand;
use ITCompass\BasePack\Commands\ExecCommand;
use ITCompass\BasePack\Commands\SslCheckCommand;
use ITCompass\BasePack\Commands\DiagnoseCommand;

class BasePackServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/basepack.php', 'basepack'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->configurePublishing();
            $this->registerCommands();
        }
    }

    /**
     * Configure the publishable resources offered by the package.
     */
    protected function configurePublishing(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/basepack.php' => config_path('basepack.php'),
        ], ['basepack-config', 'basepack']);

        // Publish Docker files (without SSL stubs)
        $this->publishes([
            __DIR__.'/../stubs/docker' => base_path('.docker'),
        ], ['basepack-docker', 'basepack'], function($source, $destination) {
            // Skip SSL certificate stubs during publishing
            return !str_contains($source, '/ssl/cert.pem') && !str_contains($source, '/ssl/key.pem');
        });

        // Publish Makefile
        $this->publishes([
            __DIR__.'/../stubs/Makefile.stub' => base_path('Makefile'),
        ], ['basepack-make', 'basepack']);

        // Publish docker-compose files
        $this->publishes([
            __DIR__.'/../stubs/docker-compose.yml.stub' => base_path('docker-compose.yml'),
            __DIR__.'/../stubs/docker-compose-prod.yml.stub' => base_path('docker-compose-prod.yml'),
        ], ['basepack-compose', 'basepack']);

        // Publish environment file
        $this->publishes([
            __DIR__.'/../stubs/.env.docker.stub' => base_path('.env.docker'),
        ], ['basepack-env', 'basepack']);
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
            BuildCommand::class,
            PublishCommand::class,
            StatusCommand::class,
            ExecCommand::class,
            SslCheckCommand::class,
            DiagnoseCommand::class,
        ]);
    }
}
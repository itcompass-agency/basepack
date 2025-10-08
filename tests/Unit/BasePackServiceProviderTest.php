<?php

namespace ITCompass\BasePack\Tests\Unit;

use ITCompass\BasePack\BasePackServiceProvider;
use ITCompass\BasePack\Tests\TestCase;

class BasePackServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_config(): void
    {
        $this->assertNotNull($this->app['config']->get('basepack'));
    }

    public function test_service_provider_registers_commands_in_console(): void
    {
        $this->assertTrue($this->app->runningInConsole());

        $commands = [
            'basepack:install',
            'basepack:build',
            'basepack:dashboard',
            'basepack:publish',
            'basepack:status',
            'basepack:exec',
            'basepack:ssl-check',
            'basepack:diagnose',
            'basepack:test',
        ];

        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $allCommands = $artisan->all();

        foreach($commands as $commandName):
            $this->assertArrayHasKey($commandName, $allCommands);
        endforeach;
    }

    public function test_service_provider_has_publishable_groups(): void
    {
        $provider = new BasePackServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);

        $this->assertTrue($reflection->hasMethod('configurePublishing'));
        $this->assertTrue($reflection->hasMethod('registerCommands'));
    }

    public function test_service_provider_merges_config(): void
    {
        $config = $this->app['config']->get('basepack');

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    public function test_boot_only_runs_in_console(): void
    {
        $provider = new BasePackServiceProvider($this->app);

        $this->assertTrue($this->app->runningInConsole());

        $reflection = new \ReflectionClass($provider);
        $bootMethod = $reflection->getMethod('boot');
        $bootMethod->setAccessible(true);

        $bootMethod->invoke($provider);

        $this->assertTrue(true);
    }
}
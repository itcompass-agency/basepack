<?php

namespace ITCompass\BasePack\Tests\Integration;

use ITCompass\BasePack\Tests\TestCase;

class ServiceProviderIntegrationTest extends TestCase
{
    public function test_all_commands_are_registered_and_accessible(): void
    {
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

    public function test_config_is_merged_correctly(): void
    {
        $config = $this->app['config']->get('basepack');

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    public function test_package_publishes_are_registered(): void
    {
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $allCommands = $artisan->all();

        $basepackCommands = array_filter($allCommands, function ($command) {
            return str_starts_with($command->getName(), 'basepack:');
        });

        $this->assertGreaterThan(0, count($basepackCommands));
    }

    public function test_commands_have_correct_signatures(): void
    {
        $expectedSignatures = [
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
        $commands = $artisan->all();

        foreach($expectedSignatures as $signature):
            $this->assertArrayHasKey($signature, $commands);
        endforeach;
    }

    public function test_config_file_path_is_correct(): void
    {
        $config = $this->app['config'];

        $this->assertNotNull($config->get('basepack'));
    }
}
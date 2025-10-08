<?php

namespace ITCompass\BasePack\Tests\Feature;

use ITCompass\BasePack\Tests\TestCase;
use Mockery;

class CommandExecutionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_publish_command_executes_successfully(): void
    {
        $this->artisan('basepack:publish', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_status_command_executes_successfully(): void
    {
        $this->artisan('basepack:status', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_build_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:build', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_exec_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:exec', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_ssl_check_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:ssl-check', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_diagnose_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:diagnose', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_dashboard_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:dashboard', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_install_command_help_executes_successfully(): void
    {
        $this->artisan('basepack:install', ['--help' => true])
            ->assertExitCode(0);
    }

    public function test_all_commands_have_descriptions(): void
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
        ];

        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $allCommands = $artisan->all();

        foreach($commands as $commandName):
            $this->assertArrayHasKey($commandName, $allCommands);
            $command = $allCommands[$commandName];
            $this->assertNotEmpty($command->getDescription());
        endforeach;
    }

    public function test_commands_fail_gracefully_with_invalid_options(): void
    {
        $this->artisan('basepack:build', ['--environment' => 'invalid'])
            ->assertExitCode(1);
    }

    public function test_package_provides_expected_functionality(): void
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
}
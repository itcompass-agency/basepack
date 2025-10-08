<?php

namespace ITCompass\BasePack\Tests\Feature;

use ITCompass\BasePack\Helpers\DockerHelper;
use ITCompass\BasePack\Helpers\EnvHelper;
use ITCompass\BasePack\Tests\TestCase;

class PackageWorkflowTest extends TestCase
{
    public function test_complete_package_installation_workflow(): void
    {
        $this->assertNotNull($this->app['config']->get('basepack'));

        $config = $this->app['config']->get('basepack');
        $this->assertIsArray($config);

        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $commands = $artisan->all();

        $expectedCommands = [
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

        foreach($expectedCommands as $commandName):
            $this->assertArrayHasKey($commandName, $commands);
        endforeach;
    }

    public function test_docker_helper_provides_expected_functionality(): void
    {
        $result = DockerHelper::isDockerRunning();
        $this->assertIsBool($result);

        $containers = DockerHelper::getProjectContainers('test-project');
        $this->assertIsArray($containers);

        $isRunning = DockerHelper::isContainerRunning('non-existent');
        $this->assertIsBool($isRunning);
    }

    public function test_env_helper_provides_expected_functionality(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'basepack_workflow_');

        try{
            file_put_contents($tempFile, "TEST_KEY=test_value\n");

            $value = EnvHelper::getEnvValue($tempFile, 'TEST_KEY');
            $this->assertEquals('test_value', $value);

            $missingValue = EnvHelper::getEnvValue($tempFile, 'MISSING_KEY', 'default');
            $this->assertEquals('default', $missingValue);

            EnvHelper::updateEnvFile($tempFile, ['NEW_KEY' => 'new_value']);

            $newValue = EnvHelper::getEnvValue($tempFile, 'NEW_KEY');
            $this->assertEquals('new_value', $newValue);

        }finally{
            if(file_exists($tempFile)):
                unlink($tempFile);
            endif;
        }
    }

    public function test_package_configuration_is_accessible(): void
    {
        $config = config('basepack');
        $this->assertIsArray($config);
    }

    public function test_commands_are_discoverable_via_artisan(): void
    {
        $output = $this->artisan('list', ['namespace' => 'basepack']);
        $output->assertExitCode(0);
    }

    public function test_package_handles_missing_dependencies_gracefully(): void
    {
        $nonExistentFile = '/path/that/does/not/exist/.env';

        $result = EnvHelper::getEnvValue($nonExistentFile, 'ANY_KEY', 'fallback');
        $this->assertEquals('fallback', $result);

        EnvHelper::updateEnvFile($nonExistentFile, ['KEY' => 'value']);

        $this->assertTrue(true);
    }
}
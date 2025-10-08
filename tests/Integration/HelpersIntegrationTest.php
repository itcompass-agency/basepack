<?php

namespace ITCompass\BasePack\Tests\Integration;

use ITCompass\BasePack\Helpers\DockerHelper;
use ITCompass\BasePack\Helpers\EnvHelper;
use ITCompass\BasePack\Tests\TestCase;

class HelpersIntegrationTest extends TestCase
{
    public function test_docker_helper_methods_work_together(): void
    {
        $projectName = 'test-integration-project';

        $isRunning = DockerHelper::isDockerRunning();
        $this->assertIsBool($isRunning);

        $containers = DockerHelper::getProjectContainers($projectName);
        $this->assertIsArray($containers);

        $isContainerRunning = DockerHelper::isContainerRunning('non-existent-container');
        $this->assertIsBool($isContainerRunning);
    }

    public function test_env_helper_creates_and_modifies_temporary_file(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'basepack_test_');

        try{
            file_put_contents($tempPath, "ORIGINAL_KEY=original_value\n");

            $value = EnvHelper::getEnvValue($tempPath, 'ORIGINAL_KEY');
            $this->assertEquals('original_value', $value);

            EnvHelper::updateEnvFile($tempPath, [
                'ORIGINAL_KEY' => 'updated_value',
                'NEW_KEY' => 'new_value'
            ]);

            $updatedValue = EnvHelper::getEnvValue($tempPath, 'ORIGINAL_KEY');
            $newValue = EnvHelper::getEnvValue($tempPath, 'NEW_KEY');

            $this->assertEquals('updated_value', $updatedValue);
            $this->assertEquals('new_value', $newValue);

        }finally{
            if(file_exists($tempPath)):
                unlink($tempPath);
            endif;
        }
    }

    public function test_env_helper_generate_docker_env_creates_proper_file(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'basepack_source_');
        $targetPath = tempnam(sys_get_temp_dir(), 'basepack_target_');

        try{
            file_put_contents($sourcePath, implode("\n", [
                'APP_NAME=TestApp',
                'DB_HOST=localhost',
                'REDIS_HOST=localhost',
                'CACHE_DRIVER=file',
                'SESSION_DRIVER=file',
            ]));

            EnvHelper::generateDockerEnv($sourcePath, $targetPath);

            $this->assertTrue(file_exists($targetPath));

            $dbHost = EnvHelper::getEnvValue($targetPath, 'DB_HOST');
            $redisHost = EnvHelper::getEnvValue($targetPath, 'REDIS_HOST');
            $cacheDriver = EnvHelper::getEnvValue($targetPath, 'CACHE_DRIVER');
            $appName = EnvHelper::getEnvValue($targetPath, 'APP_NAME');

            $this->assertEquals('mysql', $dbHost);
            $this->assertEquals('redis', $redisHost);
            $this->assertEquals('redis', $cacheDriver);
            $this->assertEquals('TestApp', $appName);

        }finally{
            foreach([$sourcePath, $targetPath] as $path):
                if(file_exists($path)):
                    unlink($path);
                endif;
            endforeach;
        }
    }

    public function test_env_helper_handles_quoted_values_correctly(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'basepack_quotes_');

        try{
            file_put_contents($tempPath, implode("\n", [
                'DOUBLE_QUOTED="double quoted value"',
                'SINGLE_QUOTED=\'single quoted value\'',
                'UNQUOTED=unquoted value',
            ]));

            $doubleQuoted = EnvHelper::getEnvValue($tempPath, 'DOUBLE_QUOTED');
            $singleQuoted = EnvHelper::getEnvValue($tempPath, 'SINGLE_QUOTED');
            $unquoted = EnvHelper::getEnvValue($tempPath, 'UNQUOTED');

            $this->assertEquals('double quoted value', $doubleQuoted);
            $this->assertEquals('single quoted value', $singleQuoted);
            $this->assertEquals('unquoted value', $unquoted);

        }finally{
            if(file_exists($tempPath)):
                unlink($tempPath);
            endif;
        }
    }
}
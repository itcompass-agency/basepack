<?php

namespace ITCompass\BasePack\Tests\Unit\Helpers;

use ITCompass\BasePack\Helpers\EnvHelper;
use ITCompass\BasePack\Tests\TestCase;

class EnvHelperSimpleTest extends TestCase
{
    public function test_env_helper_methods_exist_and_return_correct_types(): void
    {
        $this->assertTrue(method_exists(EnvHelper::class, 'updateEnvFile'));
        $this->assertTrue(method_exists(EnvHelper::class, 'getEnvValue'));
        $this->assertTrue(method_exists(EnvHelper::class, 'generateDockerEnv'));
    }

    public function test_env_helper_has_correct_method_signatures(): void
    {
        $reflection = new \ReflectionClass(EnvHelper::class);

        $updateEnvFile = $reflection->getMethod('updateEnvFile');
        $this->assertTrue($updateEnvFile->isStatic());
        $this->assertTrue($updateEnvFile->isPublic());
        $this->assertEquals(2, $updateEnvFile->getNumberOfParameters());

        $getEnvValue = $reflection->getMethod('getEnvValue');
        $this->assertTrue($getEnvValue->isStatic());
        $this->assertTrue($getEnvValue->isPublic());
        $this->assertEquals(3, $getEnvValue->getNumberOfParameters());

        $generateDockerEnv = $reflection->getMethod('generateDockerEnv');
        $this->assertTrue($generateDockerEnv->isStatic());
        $this->assertTrue($generateDockerEnv->isPublic());
        $this->assertEquals(2, $generateDockerEnv->getNumberOfParameters());
    }

    public function test_env_helper_class_structure(): void
    {
        $reflection = new \ReflectionClass(EnvHelper::class);

        $this->assertTrue($reflection->isFinal() === false);
        $this->assertCount(3, $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));

        foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method):
            $this->assertTrue($method->isStatic(), "Method {$method->getName()} should be static");
        endforeach;
    }

    public function test_env_helper_handles_empty_parameters_gracefully(): void
    {
        $result = EnvHelper::getEnvValue('/non/existent/path', 'KEY', 'default');
        $this->assertEquals('default', $result);

        $result = EnvHelper::getEnvValue('/non/existent/path', 'KEY');
        $this->assertNull($result);

        EnvHelper::updateEnvFile('/non/existent/path', []);
        EnvHelper::generateDockerEnv('/non/existent/source', '/non/existent/target');

        $this->assertTrue(true);
    }
}
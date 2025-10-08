<?php

namespace ITCompass\BasePack\Tests\Unit\Helpers;

use ITCompass\BasePack\Helpers\DockerHelper;
use ITCompass\BasePack\Tests\TestCase;

class DockerHelperSimpleTest extends TestCase
{
    public function test_docker_helper_methods_exist_and_return_correct_types(): void
    {
        $this->assertTrue(method_exists(DockerHelper::class, 'isDockerRunning'));
        $this->assertTrue(method_exists(DockerHelper::class, 'isContainerRunning'));
        $this->assertTrue(method_exists(DockerHelper::class, 'getProjectContainers'));
        $this->assertTrue(method_exists(DockerHelper::class, 'stopContainers'));
        $this->assertTrue(method_exists(DockerHelper::class, 'startContainers'));
    }

    public function test_docker_helper_has_correct_method_signatures(): void
    {
        $reflection = new \ReflectionClass(DockerHelper::class);

        $isDockerRunning = $reflection->getMethod('isDockerRunning');
        $this->assertTrue($isDockerRunning->isStatic());
        $this->assertTrue($isDockerRunning->isPublic());
        $this->assertEquals(0, $isDockerRunning->getNumberOfParameters());

        $isContainerRunning = $reflection->getMethod('isContainerRunning');
        $this->assertTrue($isContainerRunning->isStatic());
        $this->assertTrue($isContainerRunning->isPublic());
        $this->assertEquals(1, $isContainerRunning->getNumberOfParameters());

        $getProjectContainers = $reflection->getMethod('getProjectContainers');
        $this->assertTrue($getProjectContainers->isStatic());
        $this->assertTrue($getProjectContainers->isPublic());
        $this->assertEquals(1, $getProjectContainers->getNumberOfParameters());

        $stopContainers = $reflection->getMethod('stopContainers');
        $this->assertTrue($stopContainers->isStatic());
        $this->assertTrue($stopContainers->isPublic());
        $this->assertEquals(1, $stopContainers->getNumberOfParameters());

        $startContainers = $reflection->getMethod('startContainers');
        $this->assertTrue($startContainers->isStatic());
        $this->assertTrue($startContainers->isPublic());
        $this->assertEquals(2, $startContainers->getNumberOfParameters());
    }

    public function test_docker_helper_class_structure(): void
    {
        $reflection = new \ReflectionClass(DockerHelper::class);

        $this->assertTrue($reflection->isFinal() === false);
        $this->assertCount(5, $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));

        foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method):
            $this->assertTrue($method->isStatic(), "Method {$method->getName()} should be static");
        endforeach;
    }
}
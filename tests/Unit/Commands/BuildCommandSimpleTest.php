<?php

namespace ITCompass\BasePack\Tests\Unit\Commands;

use ITCompass\BasePack\Commands\BuildCommand;
use ITCompass\BasePack\Tests\TestCase;

class BuildCommandSimpleTest extends TestCase
{
    public function test_build_command_has_correct_signature(): void
    {
        $command = new BuildCommand();

        $this->assertEquals('basepack:build', $command->getName());
        $this->assertEquals('Build Docker containers for the project', $command->getDescription());
    }

    public function test_build_command_has_required_options(): void
    {
        $command = new BuildCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('environment'));
        $this->assertTrue($definition->hasOption('no-cache'));

        $envOption = $definition->getOption('environment');
        $this->assertEquals('dev', $envOption->getDefault());

        $noCacheOption = $definition->getOption('no-cache');
        $this->assertFalse($noCacheOption->getDefault());
    }

    public function test_build_command_structure(): void
    {
        $reflection = new \ReflectionClass(BuildCommand::class);

        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPublic());
    }

    public function test_build_command_extends_laravel_command(): void
    {
        $command = new BuildCommand();
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }
}
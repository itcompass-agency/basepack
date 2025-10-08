<?php

namespace ITCompass\BasePack\Tests\Unit\Commands;

use ITCompass\BasePack\Commands\TestCommand;
use ITCompass\BasePack\Tests\TestCase;

class TestCommandSimpleTest extends TestCase
{
    public function test_test_command_has_correct_signature(): void
    {
        $command = new TestCommand();

        $this->assertEquals('basepack:test', $command->getName());
        $this->assertEquals('Run tests for BasePack package', $command->getDescription());
    }

    public function test_test_command_has_required_options(): void
    {
        $command = new TestCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('suite'));
        $this->assertTrue($definition->hasOption('coverage'));
        $this->assertTrue($definition->hasOption('filter'));
        $this->assertTrue($definition->hasOption('verbose'));

        $suiteOption = $definition->getOption('suite');
        $this->assertEquals('all', $suiteOption->getDefault());

        $coverageOption = $definition->getOption('coverage');
        $this->assertFalse($coverageOption->getDefault());

        $verboseOption = $definition->getOption('verbose');
        $this->assertFalse($verboseOption->getDefault());
    }

    public function test_test_command_structure(): void
    {
        $reflection = new \ReflectionClass(TestCommand::class);

        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPublic());
    }

    public function test_test_command_extends_laravel_command(): void
    {
        $command = new TestCommand();
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    public function test_test_command_uses_display_logo_trait(): void
    {
        $reflection = new \ReflectionClass(TestCommand::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains('ITCompass\BasePack\Traits\DisplaysLogo', $traits);
    }
}
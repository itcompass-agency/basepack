<?php

namespace ITCompass\BasePack\Commands;

use ITCompass\BasePack\Traits\DisplaysLogo;
use Symfony\Component\Process\Process;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    use DisplaysLogo;

    protected $signature = 'basepack:test
                            {--suite=all : Test suite to run (unit/integration/feature/all)}
                            {--coverage : Generate coverage report}
                            {--filter= : Filter tests by name pattern}
                            {--verbose : Verbose output}';

    protected $description = 'Run tests for BasePack package';

    public function handle(): int
    {
        $this->displayLogo();

        $suite = $this->option('suite');
        $coverage = $this->option('coverage');
        $filter = $this->option('filter');
        $verbose = $this->option('verbose');

        if(!in_array($suite, ['unit', 'integration', 'feature', 'all'])):
            $this->error('Invalid test suite. Use: unit, integration, feature, or all');
            return Command::FAILURE;
        endif;

        $this->info("Running {$suite} tests...");

        if(!$this->checkPhpUnitInstallation()):
            return Command::FAILURE;
        endif;

        $command = $this->buildTestCommand($suite, $coverage, $filter, $verbose);

        $this->info("Executing: {$command}");
        $this->newLine();

        $process = Process::fromShellCommandline($command);
        $process->setTty(true);
        $process->setTimeout(300);

        $exitCode = $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->newLine();

        if($exitCode === 0):
            $this->info('✅ Tests passed successfully!');

            if($coverage):
                $this->info('📊 Coverage report generated in coverage-report/ directory');
            endif;
        else:
            $this->error('❌ Tests failed!');
        endif;

        return $exitCode;
    }

    private function checkPhpUnitInstallation(): bool
    {
        if(!file_exists('vendor/bin/phpunit')):
            $this->error('PHPUnit is not installed. Run: composer install');
            return false;
        endif;

        if(!file_exists('phpunit.xml')):
            $this->error('phpunit.xml configuration file not found');
            return false;
        endif;

        return true;
    }

    private function buildTestCommand(string $suite, bool $coverage, ?string $filter, bool $verbose): string
    {
        $command = 'vendor/bin/phpunit';

        if($suite !== 'all'):
            $command .= " --testsuite=" . ucfirst($suite);
        endif;

        if($coverage):
            $command .= ' --coverage-html coverage-report --coverage-text';
        endif;

        if($filter):
            $command .= " --filter='{$filter}'";
        endif;

        if($verbose):
            $command .= ' --verbose';
        endif;

        $command .= ' --colors=always';

        return $command;
    }
}
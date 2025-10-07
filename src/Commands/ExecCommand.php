<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ExecCommand extends Command
{
    protected $signature = 'basepack:exec 
                            {command : Command to execute}
                            {--container=laravel : Container to execute in}
                            {--root : Execute as root user}';

    protected $description = 'Execute command in Docker container';

    public function handle(): int
    {
        $command = $this->argument('command');
        $container = $this->option('container');
        $asRoot = $this->option('root');

        $makeCommand = $asRoot ? 'exec-by-root' : 'exec';
        $fullCommand = "make {$makeCommand} cmd=\"{$command}\"";

        if($container !== 'laravel'):
            $fullCommand = "docker-compose exec {$container} {$command}";
        endif;

        $this->info("Executing: {$command}");

        $process = Process::fromShellCommandline($fullCommand);
        $process->setTty(true);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
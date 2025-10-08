<?php

namespace ITCompass\BasePack\Commands;

use Symfony\Component\Process\Process;
use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'basepack:build
                            {--environment=dev : Environment to build (dev/prod)}
                            {--no-cache : Build without cache}';

    protected $description = 'Build Docker containers for the project';

    public function handle(): int
    {
        $environment = $this->option('environment');
        $noCache = $this->option('no-cache');

        if(!in_array($environment, ['dev', 'prod'])):
            $this->error('Invalid environment. Use dev or prod.');
            return Command::FAILURE;
        endif;

        $this->info("Building {$environment} environment...");

        $command = $environment === 'dev' ? 'make build' : 'make build-prod';
        
        if($noCache):
            $command .= ' DOCKER_BUILD_ARGS="--no-cache"';
        endif;

        $process = Process::fromShellCommandline($command);
        $process->setTty(true);
        $process->setTimeout(600);
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if(!$process->isSuccessful()):
            $this->error('Build failed!');
            return Command::FAILURE;
        endif;

        $this->info('Build completed successfully!');
        return Command::SUCCESS;
    }
}
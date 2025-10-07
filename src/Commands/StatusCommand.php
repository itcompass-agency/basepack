<?php

namespace ITCompass\BasePack\Commands;

use Symfony\Component\Process\Process;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'basepack:status';
    protected $description = 'Check status of Docker containers';

    public function handle(): int
    {
        $this->info('Checking Docker containers status...');

        $process = Process::fromShellCommandline('docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"');
        $process->run();

        if(!$process->isSuccessful()):
            $this->error('Failed to check Docker status. Make sure Docker is running.');
            return Command::FAILURE;
        endif;

        $output = $process->getOutput();
        $lines = explode("\n", trim($output));
        
        $containers = [];
        $projectName = strtolower(config('basepack.project_name', 'laravel'));
        
        foreach($lines as $index => $line):
            if($index === 0) continue;
            if(empty($line)) continue;
            
            if(str_contains($line, $projectName)):
                $parts = preg_split('/\s{2,}/', $line);

                if(count($parts) >= 2):
                    $containers[] = [
                        'Name' => $parts[0] ?? '',
                        'Status' => $parts[1] ?? '',
                        'Ports' => $parts[2] ?? '',
                    ];
                endif;
            endif;
        endforeach;

        if(empty($containers)):
            $this->warn('No BasePack containers are running.');
            $this->info('Run `make start` to start the containers.');
            return Command::SUCCESS;
        endif;

        $this->table(['Container', 'Status', 'Ports'], $containers);
        
        return Command::SUCCESS;
    }
}
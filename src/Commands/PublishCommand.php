<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    protected $signature = 'basepack:publish 
                            {--tag=* : Publish specific tags}
                            {--force : Overwrite existing files}';

    protected $description = 'Publish BasePack assets';

    protected array $publishGroups = [
        'docker' => 'Docker configuration files',
        'make' => 'Makefile',
        'compose' => 'Docker Compose files',
        'config' => 'Configuration file',
        'all' => 'All BasePack assets',
    ];

    public function handle(): int
    {
        $tags = $this->option('tag');
        $force = $this->option('force');

        if (empty($tags)) {
            $tags = $this->choice(
                'What would you like to publish?',
                array_keys($this->publishGroups),
                'all',
                null,
                true
            );
        }

        if (in_array('all', $tags)) {
            $tags = ['docker', 'make', 'compose', 'config'];
        }

        foreach ($tags as $tag) {
            $this->publishTag($tag, $force);
        }

        $this->info('Publishing complete!');
        return Command::SUCCESS;
    }

    protected function publishTag(string $tag, bool $force): void
    {
        $publishTag = "basepack-{$tag}";
        
        $this->call('vendor:publish', [
            '--tag' => $publishTag,
            '--force' => $force,
        ]);
    }
}

// src/Commands/StatusCommand.php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StatusCommand extends Command
{
    protected $signature = 'basepack:status';
    protected $description = 'Check status of Docker containers';

    public function handle(): int
    {
        $this->info('Checking Docker containers status...');

        $process = Process::fromShellCommandline('docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"');
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to check Docker status. Make sure Docker is running.');
            return Command::FAILURE;
        }

        $output = $process->getOutput();
        $lines = explode("\n", trim($output));
        
        $containers = [];
        $projectName = strtolower(config('basepack.project_name', 'laravel'));
        
        foreach ($lines as $index => $line) {
            if ($index === 0) continue; // Skip header
            if (empty($line)) continue;
            
            if (str_contains($line, $projectName)) {
                $parts = preg_split('/\s{2,}/', $line);
                if (count($parts) >= 2) {
                    $containers[] = [
                        'Name' => $parts[0] ?? '',
                        'Status' => $parts[1] ?? '',
                        'Ports' => $parts[2] ?? '',
                    ];
                }
            }
        }

        if (empty($containers)) {
            $this->warn('No BasePack containers are running.');
            $this->info('Run `make start` to start the containers.');
            return Command::SUCCESS;
        }

        $this->table(['Container', 'Status', 'Ports'], $containers);
        
        return Command::SUCCESS;
    }
}
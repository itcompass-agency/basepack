<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;

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

        if(empty($tags)):
            $tags = $this->choice(
                'What would you like to publish?',
                array_keys($this->publishGroups),
                'all',
                null,
                true
            );
        endif;

        if(in_array('all', $tags)):
            $tags = ['docker', 'make', 'compose', 'config'];
        endif;

        foreach($tags as $tag):
            $this->publishTag($tag, $force);
        endforeach;

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
<?php

namespace ITCompass\BasePack\Helpers;

use Symfony\Component\Process\Process;

class DockerHelper
{
    public static function isDockerRunning(): bool
    {
        $process = new Process(['docker', 'info']);
        $process->run();
        return $process->isSuccessful();
    }

    public static function isContainerRunning(string $containerName): bool
    {
        $process = new Process(['docker', 'ps', '--filter', "name={$containerName}", '--format', '{{.Names}}']);
        $process->run();
        
        if($process->isSuccessful()):
            $output = trim($process->getOutput());
            return str_contains($output, $containerName);
        endif;
        
        return false;
    }

    public static function getProjectContainers(string $projectName): array
    {
        $process = new Process(['docker', 'ps', '--filter', "label=com.docker.compose.project={$projectName}", '--format', '{{.Names}}']);
        $process->run();
        
        if($process->isSuccessful()):
            $output = trim($process->getOutput());
            return !empty($output) ? explode("\n", $output) : [];
        endif;
        
        return [];
    }

    public static function stopContainers(string $projectName): bool
    {
        $process = Process::fromShellCommandline("docker-compose -p {$projectName} down");
        $process->run();
        return $process->isSuccessful();
    }

    public static function startContainers(string $projectName, string $environment = 'dev'): bool
    {
        $composeFile = $environment === 'prod' ? 'docker-compose-prod.yml' : 'docker-compose.yml';
        $process = Process::fromShellCommandline("docker-compose -f {$composeFile} -p {$projectName} up -d");
        $process->setTimeout(180);
        $process->run();
        return $process->isSuccessful();
    }
}
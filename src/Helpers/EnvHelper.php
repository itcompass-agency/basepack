<?php

namespace ITCompass\BasePack\Helpers;

use Illuminate\Support\Facades\File;

class EnvHelper
{
    public static function updateEnvFile(string $path, array $replacements): void
    {
        if (!File::exists($path)) {
            return;
        }

        $env = File::get($path);

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $replacement, $env);
            } else {
                $env .= "\n{$replacement}";
            }
        }

        File::put($path, $env);
    }

    public static function getEnvValue(string $path, string $key, $default = null)
    {
        if (!File::exists($path)) {
            return $default;
        }

        $env = File::get($path);
        
        if (preg_match("/^{$key}=(.*)$/m", $env, $matches)) {
            $value = trim($matches[1]);
            // Remove quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            return $value;
        }
        
        return $default;
    }

    public static function generateDockerEnv(string $sourcePath, string $targetPath): void
    {
        $dockerReplacements = [
            'DB_HOST' => 'mysql',
            'REDIS_HOST' => 'redis',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
            'BROADCAST_DRIVER' => 'redis',
        ];

        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $targetPath);
            self::updateEnvFile($targetPath, $dockerReplacements);
        }
    }
}
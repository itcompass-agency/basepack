<?php

namespace ITCompass\BasePack\Commands;

use ITCompass\BasePack\Traits\DisplaysLogo;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class DiagnoseCommand extends Command
{
    use DisplaysLogo;

    protected $signature = 'basepack:diagnose 
                            {--fix : Attempt to fix common issues}';

    protected $description = 'Diagnose BasePack installation and Docker structure';

    protected $errors = [];
    protected $warnings = [];
    protected $success = [];
    protected $projectName = null;

    public function handle(): int
    {
        // Display logo
        $this->displayLogo();
        
        $this->info('🔍 Diagnosing BasePack installation...');
        $this->newLine();

        // Detect project name
        $this->detectProjectName();

        $this->checkDockerStructure();
        $this->checkEnvironmentFiles();
        $this->checkSSLCertificates();
        $this->checkDockerCompose();
        $this->checkMakefile();
        $this->checkProjectConfiguration();

        if($this->option('fix')):
            $this->attemptFixes();
        endif;

        $this->displayResults();

        return empty($this->errors) ? Command::SUCCESS : Command::FAILURE;
    }

    protected function detectProjectName(): void
    {
        if(File::exists(base_path('.env'))):
            $env = File::get(base_path('.env'));

            if(preg_match('/^COMPOSE_PROJECT_NAME=(.*)$/m', $env, $matches)):
                $this->projectName = trim($matches[1]);
            endif;
        endif;
        
        if(!$this->projectName && File::exists(base_path('Makefile'))):
            $makefile = File::get(base_path('Makefile'));
            if(preg_match('/^export COMPOSE_PROJECT_NAME=(.*)$/m', $makefile, $matches)):
                $this->projectName = trim($matches[1]);
            endif;
        endif;
        
        if(!$this->projectName):
            $sslPath = base_path('.docker/general/ssl/cert.pem');
            if(File::exists($sslPath)):
                $output = shell_exec("openssl x509 -in {$sslPath} -noout -subject 2>/dev/null");
                if($output && preg_match('/CN\s*=\s*([^\s,\/]+)/', $output, $matches)):
                    $domain = $matches[1];
                    if($domain !== 'localhost'):
                        $parts = explode('.', $domain);
                        if(count($parts) >= 2):
                            array_pop($parts); // Remove TLD
                            $this->projectName = implode('_', $parts) . '_basepack';
                        endif;
                    endif;
                endif;
            endif;
        endif;

        // Default
        if(!$this->projectName):
            $this->projectName = 'basepack';
        endif;
    }

    protected function checkProjectConfiguration(): void
    {
        $this->info('📦 Project Configuration:');
        $this->line('  Project Name: ' . $this->projectName);
        
        $runningContainers = shell_exec("docker ps --filter 'name={$this->projectName}' --format '{{.Names}}'");
        if($runningContainers):
            $containers = explode("\n", trim($runningContainers));
            $this->success[] = "✅ Found " . count($containers) . " running container(s) for project: {$this->projectName}";

            foreach($containers as $container):
                if(!empty($container)):
                    $this->line('    • ' . $container);
                endif;
            endforeach;
        else:
            $this->warnings[] = "⚠️  No running containers found for project: {$this->projectName}";
        endif;
    }

    protected function checkDockerStructure(): void
    {
        $requiredFiles = [
            '.docker/Dockerfile' => 'Main Laravel Dockerfile',
            '.docker/nginx/Dockerfile' => 'Nginx Dockerfile',
            '.docker/dev/nginx.conf' => 'Dev Nginx config',
            '.docker/prod/nginx.conf' => 'Prod Nginx config',
            '.docker/dev/php.ini' => 'Dev PHP config',
            '.docker/prod/php.ini' => 'Prod PHP config',
            '.docker/dev/www.conf' => 'Dev PHP-FPM config',
            '.docker/prod/www.conf' => 'Prod PHP-FPM config',
            '.docker/dev/xdebug-main.ini' => 'Xdebug main config',
            '.docker/dev/xdebug-osx.ini' => 'Xdebug OSX config',
            '.docker/dev/init-db.sql' => 'Database init script',
            '.docker/general/do_we_need_xdebug.sh' => 'Xdebug installer script',
            '.docker/general/supervisord.conf' => 'Supervisor config',
            '.docker/general/cron' => 'Cron config',
        ];

        foreach($requiredFiles as $path => $description):
            if(File::exists(base_path($path))):
                $this->success[] = "✅ {$description}: {$path}";
            else:
                $this->errors[] = "❌ Missing: {$description} ({$path})";
            endif;
        endforeach;

        // Check SSL certificates separately
        $sslPath = base_path('.docker/general/ssl');

        if(File::exists($sslPath)):
            if(File::exists($sslPath . '/cert.pem') && File::exists($sslPath . '/key.pem')):
                $this->success[] = "✅ SSL certificates found in Docker directory";
                
                // Check certificate details
                $certInfo = $this->getCertificateInfo($sslPath . '/cert.pem');

                if($certInfo):
                    $this->line('  Certificate Details:');

                    foreach($certInfo as $key => $value):
                        $this->line("    • {$key}: {$value}");
                    endforeach;
                endif;
            else:
                $this->warnings[] = "⚠️  SSL directory exists but certificates missing";
            endif;
        else:
            $this->errors[] = "❌ SSL directory missing: .docker/general/ssl/";
        endif;
    }

    protected function getCertificateInfo(string $certPath): ?array
    {
        if(!File::exists($certPath)):
            return null;
        endif;

        $output = shell_exec("openssl x509 -in {$certPath} -noout -subject -dates 2>/dev/null");
        
        if(!$output):
            return null;
        endif;

        $info = [];
        
        // Extract domain/CN
        if(preg_match('/CN\s*=\s*([^\s,\/]+)/', $output, $matches)):
            $info['Domain'] = $matches[1];
        endif;
        
        // Extract validity dates
        if(preg_match('/notBefore=(.+)/', $output, $matches)):
            $info['Valid from'] = date('Y-m-d', strtotime($matches[1]));
        endif;
        
        if(preg_match('/notAfter=(.+)/', $output, $matches)):
            $notAfter = strtotime($matches[1]);
            $info['Valid until'] = date('Y-m-d', $notAfter);
            
            // Check if expired or expiring soon
            $daysLeft = ($notAfter - time()) / (60 * 60 * 24);
            if($daysLeft < 0):
                $info['Status'] = '❌ EXPIRED';
            elseif ($daysLeft < 30):
                $info['Status'] = '⚠️  Expiring soon (' . round($daysLeft) . ' days)';
            else:
                $info['Status'] = '✅ Valid (' . round($daysLeft) . ' days remaining)';
            endif;
        endif;
        
        return $info;
    }

    protected function checkEnvironmentFiles(): void
    {
        $envFiles = [
            '.env' => 'Main environment file',
            '.env.docker' => 'Docker environment template',
        ];

        foreach($envFiles as $file => $description):
            $path = base_path($file);
            if(File::exists($path)):
                $content = File::get($path);
                
                $requiredVars = [
                    'DB_HOST',
                    'DB_PORT',
                    'DB_DATABASE',
                    'DB_USERNAME',
                    'DB_PASSWORD',
                    'DB_OUTER_PORT',
                    'REDIS_HOST',
                    'REDIS_OUTER_PORT',
                    'WEB_PORT_HTTP',
                    'WEB_PORT_SSL',
                    'COMPOSE_PROJECT_NAME',
                ];

                $missingVars = [];

                foreach($requiredVars as $var):
                    if(!preg_match("/^{$var}=/m", $content)):
                        $missingVars[] = $var;
                    endif;
                endforeach;

                if(empty($missingVars)):
                    $this->success[] = "✅ {$description}: All required variables present";
                else:
                    $this->warnings[] = "⚠️  {$description}: Missing variables: " . implode(', ', $missingVars);
                endif;
            else:
                $this->errors[] = "❌ Missing: {$description} ({$file})";
            endif;
        endforeach;
    }

    protected function checkSSLCertificates(): void
    {
        $sslPaths = [
            '.docker/general/ssl',
            '.ssl',
            'ssl',
            'certificates',
        ];

        $found = false;

        foreach($sslPaths as $path):
            $fullPath = base_path($path);

            if(File::exists($fullPath . '/cert.pem') && File::exists($fullPath . '/key.pem')):
                $this->success[] = "✅ SSL certificates found in: {$path}/";
                $found = true;
            endif;
        endforeach;

        if(!$found):
            $this->errors[] = "❌ No SSL certificates found in any standard location";
        endif;
    }

    protected function checkDockerCompose(): void
    {
        $composeFiles = [
            'docker-compose.yml' => 'Development compose file',
            'docker-compose-prod.yml' => 'Production compose file',
        ];

        foreach($composeFiles as $file => $description):
            if(File::exists(base_path($file))):
                $this->success[] = "✅ {$description} exists";
                
                $content = File::get(base_path($file));

                if(!str_contains($content, $this->projectName)):
                    $this->warnings[] = "⚠️  {$file} may have incorrect project name";
                endif;
            else:
                $this->warnings[] = "⚠️  Missing: {$description}";
            endif;
        endforeach;
    }

    protected function checkMakefile(): void
    {
        if(File::exists(base_path('Makefile'))):
            $this->success[] = "✅ Makefile exists";
            
            $content = File::get(base_path('Makefile'));
            if(!str_contains($content, "COMPOSE_PROJECT_NAME={$this->projectName}")):
                $this->warnings[] = "⚠️  Makefile may have incorrect project name";
            endif;
        else:
            $this->errors[] = "❌ Missing: Makefile";
        endif;
    }

    protected function attemptFixes(): void
    {
        $this->info('🔧 Attempting to fix issues...');
        $this->newLine();
        
        if(!File::exists(base_path('.env'))):
            if(File::exists(base_path('.env.docker'))):
                File::copy(base_path('.env.docker'), base_path('.env'));
                $this->info('Fixed: Created .env from .env.docker');
            elseif(File::exists(base_path('.env.example'))):
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->updateEnvForDocker(base_path('.env'));
                $this->info('Fixed: Created .env from .env.example');
            endif;
        endif;
        
        $this->fixEnvironmentVariables();
        
        if(!File::exists(base_path('.docker/general/ssl'))):
            File::makeDirectory(base_path('.docker/general/ssl'), 0755, true);
            $this->info('Fixed: Created SSL directory');

            $sslSources = ['.ssl', 'ssl', 'certificates'];
            foreach($sslSources as $source):
                $sourcePath = base_path($source);
                if(File::exists($sourcePath . '/cert.pem') && File::exists($sourcePath . '/key.pem')):
                    File::copy($sourcePath . '/cert.pem', base_path('.docker/general/ssl/cert.pem'));
                    File::copy($sourcePath . '/key.pem', base_path('.docker/general/ssl/key.pem'));
                    $this->info("Fixed: Copied SSL certificates from {$source}/");
                    break;
                endif;
            endforeach;
        endif;
        
        $this->fixProjectNameConsistency();
    }

    protected function fixProjectNameConsistency(): void
    {
        $updated = false;
        
        if(File::exists(base_path('.env'))):
            $env = File::get(base_path('.env'));

            if(!preg_match('/^COMPOSE_PROJECT_NAME=/m', $env)):
                $env .= "\nCOMPOSE_PROJECT_NAME={$this->projectName}\n";
                File::put(base_path('.env'), $env);
                $updated = true;
            endif;
        endif;
        
        if(File::exists(base_path('Makefile'))):
            $makefile = File::get(base_path('Makefile'));
            $makefile = preg_replace(
                '/^export COMPOSE_PROJECT_NAME=.*$/m',
                "export COMPOSE_PROJECT_NAME={$this->projectName}",
                $makefile
            );
            File::put(base_path('Makefile'), $makefile);
            $updated = true;
        endif;

        if($updated):
            $this->info("Fixed: Updated project name to '{$this->projectName}' across configuration files");
        endif;
    }

    protected function fixEnvironmentVariables(): void
    {
        if(!File::exists(base_path('.env'))):
            return;
        endif;

        $env = File::get(base_path('.env'));
        $updated = false;

        $defaults = [
            'DB_HOST' => 'mysql',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'laravel',
            'DB_USERNAME' => 'laravel',
            'DB_PASSWORD' => 'secret',
            'DB_OUTER_PORT' => '3306',
            'REDIS_HOST' => 'redis',
            'REDIS_PORT' => '6379',
            'REDIS_OUTER_PORT' => '6379',
            'WEB_PORT_HTTP' => '80',
            'WEB_PORT_SSL' => '443',
            'XDEBUG_CONFIG' => 'main',
            'INNODB_USE_NATIVE_AIO' => '1',
            'COMPOSE_PROJECT_NAME' => $this->projectName,
        ];

        foreach($defaults as $key => $value):
            if(!preg_match("/^{$key}=/m", $env)):
                $env .= "\n{$key}={$value}";
                $updated = true;
            endif;
        endforeach;

        if($updated):
            File::put(base_path('.env'), $env);
            $this->info('Fixed: Added missing environment variables');
        endif;
    }

    protected function updateEnvForDocker(string $path): void
    {
        $env = File::get($path);
        
        $replacements = [
            'DB_HOST=127.0.0.1' => 'DB_HOST=mysql',
            'REDIS_HOST=127.0.0.1' => 'REDIS_HOST=redis',
            'CACHE_DRIVER=file' => 'CACHE_DRIVER=redis',
            'SESSION_DRIVER=file' => 'SESSION_DRIVER=redis',
            'QUEUE_CONNECTION=sync' => 'QUEUE_CONNECTION=redis',
        ];

        foreach($replacements as $search => $replace):
            $env = preg_replace('/^'.preg_quote($search, '/').'$/m', $replace, $env);
        endforeach;

        File::put($path, $env);
    }

    protected function displayResults(): void
    {
        $this->newLine();
        
        if(!empty($this->success)):
            $this->info('✅ Successful checks:');

            foreach($this->success as $message):
                $this->line("  {$message}");
            endforeach;

            $this->newLine();
        endif;

        if(!empty($this->warnings)):
            $this->warn('⚠️  Warnings:');

            foreach($this->warnings as $message):
                $this->line("  {$message}");
            endforeach;

            $this->newLine();
        endif;

        if(!empty($this->errors)):
            $this->error('❌ Errors:');

            foreach($this->errors as $message):
                $this->line("  {$message}");
            endforeach;

            $this->newLine();
        endif;

        // Summary
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                      📊 Diagnosis Summary                      ║');
        $this->line('╠════════════════════════════════════════════════════════════════╣');
        $this->line('║  Project: ' . str_pad($this->projectName, 53) . '║');
        $this->line('║  ✅ Success:  ' . str_pad(count($this->success) . ' checks passed', 49) . '║');
        $this->line('║  ⚠️  Warnings: ' . str_pad(count($this->warnings) . ' issues found', 49) . '║');
        $this->line('║  ❌ Errors:   ' . str_pad(count($this->errors) . ' critical issues', 49) . '║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');

        if(empty($this->errors)):
            $this->newLine();
            $this->info('🎉 BasePack installation looks good!');
            
            if(!empty($this->warnings)):
                $this->info('Consider addressing the warnings for optimal performance.');
            endif;
        else:
            $this->newLine();
            $this->error('⚠️  Issues found! Run with --fix flag to attempt automatic fixes:');
            $this->line('  php artisan basepack:diagnose --fix');
        endif;
    }
}
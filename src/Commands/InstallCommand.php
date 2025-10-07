<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'basepack:install 
                            {--dev : Install development environment}
                            {--prod : Install production environment}
                            {--force : Overwrite existing files}
                            {--ssl-path= : Path to SSL certificates directory}
                            {--domain=localhost : Domain name for the application}';

    protected $description = 'Install BasePack DevOps toolkit';

    protected $sslCertPath = null;
    protected $sslKeyPath = null;
    protected $domain = 'localhost';

    public function handle(): int
    {
        $this->info('Installing BasePack DevOps toolkit...');

        $environment = $this->option('dev') ? 'dev' : ($this->option('prod') ? 'prod' : null);
        
        if(!$environment):
            $environment = $this->choice(
                'Which environment would you like to install?',
                ['dev', 'prod', 'both'],
                'dev'
            );
        endif;

        $force = $this->option('force');
        $this->domain = $this->option('domain') ?: 'localhost';
        
        if(!$this->checkSSLCertificates()):
            return Command::FAILURE;
        endif;
        
        $this->createDockerEnv($force);
        $this->publishDockerFiles($force);
        $this->publishMakefile($force);
        $this->publishDockerCompose($environment, $force);
        $this->updateGitignore();

        $this->info('BasePack installed successfully!');
        $this->newLine();
        
        $this->table(
            ['Next Steps'],
            [
                ['1. Review and update .env file with your database credentials'],
                ['2. Run: make build'],
                ['3. Run: make start'],
                ['4. Run: make composer-install'],
                ['5. Run: make migrate'],
            ]
        );

        return Command::SUCCESS;
    }

    protected function checkSSLCertificates(): bool
    {
        $sslPath = $this->option('ssl-path');
        
        if($sslPath):
            $certPath = rtrim($sslPath, '/') . '/cert.pem';
            $keyPath = rtrim($sslPath, '/') . '/key.pem';
            
            if(!File::exists($certPath) || !File::exists($keyPath)):
                $this->error("SSL certificates not found in provided path: {$sslPath}");
                $this->error("Expected files: cert.pem and key.pem");
                return false;
            endif;
            
            $this->sslCertPath = $certPath;
            $this->sslKeyPath = $keyPath;
            
            $this->extractDomainFromCertificate($certPath);
            
            $this->info("Using SSL certificates from: {$sslPath}");
            $this->info("Domain: {$this->domain}");
            
        else:
            $defaultLocations = [
                base_path('ssl'),
                base_path('.ssl'),
                base_path('certificates'),
                base_path('.docker/ssl'),
                '/etc/ssl/certs',
                $_SERVER['HOME'] . '/.ssl',
            ];
            
            $found = false;

            foreach($defaultLocations as $location):
                if(File::exists($location . '/cert.pem') && File::exists($location . '/key.pem')):
                    $this->sslCertPath = $location . '/cert.pem';
                    $this->sslKeyPath = $location . '/key.pem';
                    $found = true;
                    
                    $this->extractDomainFromCertificate($this->sslCertPath);
                    
                    $this->info("Found SSL certificates in: {$location}");
                    $this->info("Domain: {$this->domain}");
                    break;
                endif;
            endforeach;
            
            if(!$found):
                $this->error('SSL certificates not found!');
                $this->error('');
                $this->error('BasePack requires SSL certificates to continue installation.');
                $this->error('');
                $this->error('Please provide SSL certificates in one of the following ways:');
                $this->error('');
                $this->error('Option 1: Place certificates in one of these locations:');

                foreach($defaultLocations as $location):
                    $this->warn("  - {$location}/cert.pem and {$location}/key.pem");
                endforeach;

                $this->error('');
                $this->error('Option 2: Specify the path using --ssl-path option:');
                $this->warn('  php artisan basepack:install --ssl-path=/path/to/ssl');
                $this->error('');
                $this->error('Certificate files must be named: cert.pem and key.pem');
                $this->error('');
                $this->error('To generate self-signed certificates for development, you can use:');
                $this->warn('  openssl req -x509 -nodes -days 365 -newkey rsa:2048 \\');
                $this->warn('    -keyout key.pem -out cert.pem \\');
                $this->warn('    -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"');
                
                return false;
            endif;
        endif;
        
        return true;
    }

    protected function extractDomainFromCertificate(string $certPath): void
    {
        if(!File::exists($certPath)):
            return;
        endif;
        
        $certContent = File::get($certPath);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'cert');
        File::put($tempFile, $certContent);
        
        $output = shell_exec("openssl x509 -in {$tempFile} -noout -subject 2>/dev/null");
        
        if($output):
            if(preg_match('/CN\s*=\s*([^\s,\/]+)/', $output, $matches)):
                $extractedDomain = $matches[1];
                
                if($this->option('domain') === null || $this->option('domain') === 'localhost'):
                    $this->domain = $extractedDomain;
                endif;
            endif;
        endif;
        
        @unlink($tempFile);
    }

    protected function publishDockerFiles(bool $force): void
    {
        $source = __DIR__.'/../../stubs/docker';
        $destination = base_path('.docker');

        if(File::exists($destination) && !$force):
            if(!$this->confirm('.docker directory already exists. Do you want to overwrite it?')):
                return;
            endif;
        endif;
        
        $this->copyDirectoryExceptSSL($source, $destination);
        
        if($this->sslCertPath && $this->sslKeyPath):
            $sslDestination = $destination . '/general/ssl';
            
            if(!File::exists($sslDestination)):
                File::makeDirectory($sslDestination, 0755, true);
            endif;
            
            File::copy($this->sslCertPath, $sslDestination . '/cert.pem');
            File::copy($this->sslKeyPath, $sslDestination . '/key.pem');
            
            $this->info('SSL certificates copied to .docker/general/ssl/');
        endif;
        
        $this->updateNginxConfig($destination);
        
        $this->info('Docker files published successfully.');
    }

    protected function copyDirectoryExceptSSL(string $source, string $destination): void
    {
        if(!File::exists($destination)):
            File::makeDirectory($destination, 0755, true);
        endif;
        
        $iterator = new \RecursiveIteratorIterator(
            iterator: new \RecursiveDirectoryIterator(
                directory: $source, 
                flags: \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            mode: \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach($iterator as $item):
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            
            // Skip SSL certificate stub files
            if(strpos($relativePath, 'general' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cert.pem') !== false ||
                strpos($relativePath, 'general' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'key.pem') !== false):
                continue;
            endif;
            
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;
            
            if($item->isDir()):
                if(!File::exists($targetPath)):
                    File::makeDirectory($targetPath, 0755, true);
                endif;
            else:
                $targetDir = dirname($targetPath);

                if(!File::exists($targetDir)):
                    File::makeDirectory($targetDir, 0755, true);
                endif;

                File::copy($item->getPathname(), $targetPath);
            endif;
        endforeach;
    }

    protected function updateNginxConfig(string $dockerPath): void
    {
        $nginxConfigs = [
            $dockerPath . '/dev/nginx.conf',
            $dockerPath . '/prod/nginx.conf'
        ];
        
        foreach($nginxConfigs as $configPath):
            if(File::exists($configPath)):
                $content = File::get($configPath);
                
                // Replace domain placeholders
                $content = str_replace('basepack.dev', $this->domain, $content);
                $content = str_replace('basepack.io', $this->domain, $content);
                $content = str_replace('localhost', $this->domain, $content);
                
                File::put($configPath, $content);
            endif;
        endforeach;
    }

    protected function publishMakefile(bool $force): void
    {
        $source = __DIR__.'/../../stubs/Makefile.stub';
        $destination = base_path('Makefile');

        if(File::exists($destination) && !$force):
            if(!$this->confirm('Makefile already exists. Do you want to overwrite it?')):
                return;
            endif;
        endif;

        // Replace placeholders in Makefile
        $content = File::get($source);
        $content = str_replace(
            ['{{PROJECT_NAME}}', '{{APP_NAME}}'],
            [
                strtolower(str_replace(' ', '-', config('app.name', 'laravel'))),
                config('app.name', 'Laravel')
            ],
            $content
        );

        File::put($destination, $content);
        $this->info('Makefile published successfully.');
    }

    protected function publishDockerCompose(string $environment, bool $force): void
    {
        $files = [];
        
        if($environment === 'dev' || $environment === 'both'):
            $files['docker-compose.yml.stub'] = 'docker-compose.yml';
        endif;
        
        if($environment === 'prod' || $environment === 'both'):
            $files['docker-compose-prod.yml.stub'] = 'docker-compose-prod.yml';
        endif;

        foreach($files as $source => $destination):
            $sourcePath = __DIR__.'/../../stubs/'.$source;
            $destinationPath = base_path($destination);

            if(File::exists($destinationPath) && !$force):
                if (!$this->confirm("$destination already exists. Do you want to overwrite it?")):
                    continue;
                endif;
            endif;

            $content = File::get($sourcePath);
            // Replace placeholders
            $content = str_replace(
                '{{PROJECT_NAME}}',
                strtolower(str_replace(' ', '-', config('app.name', 'laravel'))),
                $content
            );

            File::put($destinationPath, $content);
            $this->info("$destination published successfully.");
        endforeach;
    }

    protected function createDockerEnv(bool $force): void
    {
        $envExample = base_path('.env.example');
        $envDocker = base_path('.env.docker');
        $env = base_path('.env');
        
        if(File::exists($envDocker) && !$force):
            return;
        endif;
        
        if(File::exists($envExample)):
            File::copy($envExample, $envDocker);
        else:
            $defaultEnv = File::get(__DIR__.'/../../stubs/.env.docker.stub');
            File::put($envDocker, $defaultEnv);
        endif;
        
        $this->updateEnvFile($envDocker);
        
        if(!File::exists($env)):
            File::copy($envDocker, $env);
            $this->info('.env file created from .env.docker');
        endif;
        
        $this->info('.env.docker file created successfully.');
    }

    protected function updateEnvFile(string $path): void
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
        
        if (!str_contains($env, 'APP_DOMAIN=')) {
            $env .= "\n# BasePack Configuration\n";
            $env .= "APP_DOMAIN={$this->domain}\n";
        }

        File::put($path, $env);
    }

    protected function updateGitignore(): void
    {
        $gitignore = base_path('.gitignore');
        
        if(!File::exists($gitignore)):
            return;
        endif;

        $content = File::get($gitignore);
        $toAdd = [
            '.docker/general/ssl/*.pem',
            '.docker/general/ssl/*.key',
            '.docker/general/ssl/*.crt',
            '.env.docker',
            'storage/mysql-data/',
            'storage/redis-data/',
        ];

        foreach($toAdd as $line):
            if(!str_contains($content, $line)):
                $content .= "\n" . $line;
            endif;
        endforeach;

        File::put($gitignore, $content);
        $this->info('.gitignore updated successfully.');
    }
}
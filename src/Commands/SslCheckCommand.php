<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class SslCheckCommand extends Command
{
    protected $signature = 'basepack:ssl-check 
                            {--show-locations : Show all checked locations}';

    protected $description = 'Check SSL certificate status and configuration';

    public function handle(): int
    {
        $this->info('Checking SSL certificates...');
        $this->newLine();

        $defaultLocations = [
            base_path('.docker/general/ssl') => 'Docker SSL directory',
            base_path('ssl') => 'Project SSL directory',
            base_path('.ssl') => 'Hidden SSL directory',
            base_path('certificates') => 'Certificates directory',
            '/etc/ssl/certs' => 'System SSL directory',
            $_SERVER['HOME'] . '/.ssl' => 'User SSL directory',
        ];

        $foundLocations = [];
        $showLocations = $this->option('show-locations');

        foreach($defaultLocations as $location => $description):
            $certPath = $location . '/cert.pem';
            $keyPath = $location . '/key.pem';
            
            if($showLocations):
                $certExists = File::exists($certPath) ? '✓' : '✗';
                $keyExists = File::exists($keyPath) ? '✓' : '✗';
                
                $this->line(sprintf(
                    "[%s cert.pem | %s key.pem] %s",
                    $certExists,
                    $keyExists,
                    $location
                ));
            endif;
            
            if(File::exists($certPath) && File::exists($keyPath)):
                $foundLocations[] = [
                    'path' => $location,
                    'description' => $description,
                    'cert' => $certPath,
                    'key' => $keyPath,
                ];
            endif;
        endforeach;

        $this->newLine();

        if(empty($foundLocations)):
            $this->error('No SSL certificates found!');
            $this->newLine();
            $this->warn('BasePack requires SSL certificates for HTTPS support.');
            $this->warn('Please place your SSL certificates (cert.pem and key.pem) in one of the following locations:');
            $this->newLine();
            
            foreach($defaultLocations as $location => $description):
                $this->line("  • {$location}/ ({$description})");
            endforeach;
            
            $this->newLine();
            $this->info('To generate self-signed certificates for development:');
            $this->newLine();
            $this->line('mkdir -p .docker/general/ssl');
            $this->line('openssl req -x509 -nodes -days 365 -newkey rsa:2048 \\');
            $this->line('  -keyout .docker/general/ssl/key.pem \\');
            $this->line('  -out .docker/general/ssl/cert.pem \\');
            $this->line('  -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"');
            
            return Command::FAILURE;
        endif;

        $this->info('SSL certificates found:');
        $this->newLine();

        foreach($foundLocations as $location):
            $this->line("📁 {$location['description']}");
            $this->line("   Path: {$location['path']}");
            
            $certInfo = $this->getCertificateInfo($location['cert']);

            if($certInfo):
                foreach($certInfo as $key => $value):
                    $this->line("   {$key}: {$value}");
                endforeach;
            endif;
            
            $this->newLine();
        endforeach;
        
        $dockerSslPath = base_path('.docker/general/ssl');
        $hasDockerSsl = false;
        
        foreach($foundLocations as $location):
            if($location['path'] === $dockerSslPath):
                $hasDockerSsl = true;
                break;
            endif;
        endforeach;

        if(!$hasDockerSsl && !empty($foundLocations)):
            $this->warn('⚠️  SSL certificates found but not in Docker directory.');
            $this->warn('   You may need to copy them to: ' . $dockerSslPath);
            $this->newLine();
            
            $firstLocation = $foundLocations[0];
            $this->info('To copy certificates to Docker directory:');
            $this->line("mkdir -p {$dockerSslPath}");
            $this->line("cp {$firstLocation['cert']} {$dockerSslPath}/cert.pem");
            $this->line("cp {$firstLocation['key']} {$dockerSslPath}/key.pem");
        endif;

        return Command::SUCCESS;
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
            
            $daysLeft = ($notAfter - time()) / (60 * 60 * 24);

            if ($daysLeft < 0):
                $info['Status'] = '❌ EXPIRED';
            elseif ($daysLeft < 30):
                $info['Status'] = '⚠️  Expiring soon (' . round($daysLeft) . ' days)';
            else:
                $info['Status'] = '✅ Valid (' . round($daysLeft) . ' days remaining)';
            endif;
        endif;
        
        return $info;
    }
}
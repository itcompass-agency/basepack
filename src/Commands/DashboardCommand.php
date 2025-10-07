<?php

namespace ITCompass\BasePack\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ITCompass\BasePack\Traits\DisplaysLogo;

class DashboardCommand extends Command
{
    use DisplaysLogo;

    protected $signature = 'basepack:dashboard 
                            {--refresh=5 : Auto-refresh interval in seconds (0 to disable)}';

    protected $description = 'Display BasePack containers dashboard with real-time status';

    protected $projectName = null;

    public function handle(): int
    {
        // Detect project name
        $this->detectProjectName();
        
        $refreshInterval = (int) $this->option('refresh');
        
        if ($refreshInterval > 0) {
            $this->info("Dashboard will refresh every {$refreshInterval} seconds. Press Ctrl+C to exit.");
            $this->newLine();
            
            while (true) {
                $this->clearScreen();
                $this->displayDashboard();
                sleep($refreshInterval);
            }
        } else {
            $this->displayDashboard();
        }

        return Command::SUCCESS;
    }

    protected function detectProjectName(): void
    {
        // Try to get from environment
        if (File::exists(base_path('.env'))) {
            $env = File::get(base_path('.env'));
            if (preg_match('/^COMPOSE_PROJECT_NAME=(.*)$/m', $env, $matches)) {
                $this->projectName = trim($matches[1]);
            }
        }

        // Try to get from Makefile
        if (!$this->projectName && File::exists(base_path('Makefile'))) {
            $makefile = File::get(base_path('Makefile'));
            if (preg_match('/^export COMPOSE_PROJECT_NAME=(.*)$/m', $makefile, $matches)) {
                $this->projectName = trim($matches[1]);
            }
        }

        // Default
        if (!$this->projectName) {
            $this->projectName = 'basepack';
        }
    }

    protected function clearScreen(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }

    protected function displayDashboard(): void
    {
        // Display logo
        $this->displayCompactLogo();
        
        // Header
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                    🎛️  BASEPACK DASHBOARD                      ║');
        $this->line('╠════════════════════════════════════════════════════════════════╣');
        $this->line('║  Project: ' . str_pad($this->projectName, 53) . '║');
        $this->line('║  Time: ' . str_pad(date('Y-m-d H:i:s'), 56) . '║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Docker status
        $this->displayDockerStatus();
        $this->newLine();

        // Containers
        $this->displayContainers();
        $this->newLine();

        // Resources
        $this->displayResources();
        $this->newLine();

        // Network & Ports
        $this->displayNetworkInfo();
        $this->newLine();

        // Quick Actions
        $this->displayQuickActions();
    }

    protected function displayDockerStatus(): void
    {
        $this->info('📦 Docker Status:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Check if Docker is running
        $dockerRunning = $this->isDockerRunning();
        
        if ($dockerRunning) {
            $this->line('  Status: <fg=green>● Running</>');
            
            // Get Docker version
            $version = shell_exec('docker --version 2>/dev/null');
            if ($version) {
                $this->line('  Version: ' . trim($version));
            }
            
            // Get Docker info
            $info = shell_exec('docker info --format "{{.ServerVersion}}" 2>/dev/null');
            if ($info) {
                $this->line('  Server Version: ' . trim($info));
            }
        } else {
            $this->line('  Status: <fg=red>● Not Running</> - Please start Docker');
        }
    }

    protected function displayContainers(): void
    {
        $this->info('🐳 Containers:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $containers = $this->getContainersInfo();
        
        if (empty($containers)) {
            $this->warn('  No containers found for project: ' . $this->projectName);
            $this->line('  Run: <fg=cyan>make start</> to start containers');
            return;
        }

        foreach ($containers as $container) {
            $statusIcon = $container['running'] ? '<fg=green>●</>' : '<fg=red>●</>';
            $statusText = $container['running'] ? '<fg=green>Running</>' : '<fg=red>Stopped</>';
            
            $this->line('');
            $this->line("  {$statusIcon} <fg=yellow>{$container['name']}</>");
            $this->line("     Status: {$statusText}");
            $this->line("     Uptime: {$container['uptime']}");
            
            if (!empty($container['ports'])) {
                $this->line("     Ports: {$container['ports']}");
            }
            
            if (!empty($container['health'])) {
                $healthIcon = $container['health'] === 'healthy' ? '✓' : 
                             ($container['health'] === 'unhealthy' ? '✗' : '◐');
                $this->line("     Health: {$healthIcon} {$container['health']}");
            }
            
            // CPU & Memory
            if ($container['running']) {
                $stats = $this->getContainerStats($container['id']);
                if ($stats) {
                    $this->line("     CPU: {$stats['cpu']}  |  Memory: {$stats['memory']}");
                }
            }
        }
    }

    protected function displayResources(): void
    {
        $this->info('💾 System Resources:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Docker system info
        $dfOutput = shell_exec('docker system df 2>/dev/null');
        
        if ($dfOutput) {
            $lines = explode("\n", trim($dfOutput));
            foreach ($lines as $line) {
                if (!empty(trim($line)) && !str_contains($line, 'TYPE')) {
                    $this->line('  ' . $line);
                }
            }
        }
    }

    protected function displayNetworkInfo(): void
    {
        $this->info('🌐 Network & Ports:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Get exposed ports
        $ports = $this->getExposedPorts();
        
        if (!empty($ports)) {
            foreach ($ports as $service => $servicePort) {
                $icon = $this->getServiceIcon($service);
                $this->line("  {$icon} {$service}: <fg=cyan>{$servicePort}</>");
            }
        } else {
            $this->line('  No exposed ports found');
        }
        
        $this->newLine();
        
        // Network
        $network = shell_exec("docker network ls --filter name={$this->projectName} --format '{{.Name}}' 2>/dev/null");
        if ($network) {
            $this->line("  Network: <fg=yellow>" . trim($network) . "</>");
        }
    }

    protected function displayQuickActions(): void
    {
        $this->info('⚡ Quick Actions:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $actions = [
            'make start' => 'Start all containers',
            'make stop' => 'Stop all containers',
            'make restart' => 'Restart all containers',
            'make ssh' => 'SSH into Laravel container',
            'make logs' => 'View Laravel logs',
            'php artisan basepack:diagnose' => 'Run diagnostics',
        ];
        
        foreach ($actions as $command => $description) {
            $this->line("  <fg=cyan>{$command}</> - {$description}");
        }
    }

    protected function isDockerRunning(): bool
    {
        $result = shell_exec('docker info 2>/dev/null');
        return !empty($result);
    }

    protected function getContainersInfo(): array
    {
        $cmd = "docker ps -a --filter 'name={$this->projectName}' --format '{{.ID}}|{{.Names}}|{{.Status}}|{{.Ports}}' 2>/dev/null";
        $output = shell_exec($cmd);
        
        if (empty($output)) {
            return [];
        }
        
        $containers = [];
        $lines = explode("\n", trim($output));
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $parts = explode('|', $line);
            if (count($parts) < 3) continue;
            
            $id = $parts[0];
            $name = $parts[1];
            $status = $parts[2];
            $ports = $parts[3] ?? '';
            
            $running = str_starts_with($status, 'Up');
            
            // Extract uptime
            $uptime = $this->extractUptime($status);
            
            // Check health
            $health = null;
            if (preg_match('/\(([^)]+)\)/', $status, $matches)) {
                $health = strtolower($matches[1]);
            }
            
            $containers[] = [
                'id' => $id,
                'name' => $name,
                'running' => $running,
                'status' => $status,
                'uptime' => $uptime,
                'ports' => $ports,
                'health' => $health,
            ];
        }
        
        return $containers;
    }

    protected function extractUptime(string $status): string
    {
        if (preg_match('/Up\s+(.+?)(\s+\(|$)/', $status, $matches)) {
            return trim($matches[1]);
        }
        
        if (str_starts_with($status, 'Exited')) {
            return 'Stopped';
        }
        
        return 'Unknown';
    }

    protected function getContainerStats(string $containerId): ?array
    {
        $cmd = "docker stats --no-stream --format '{{.CPUPerc}}|{{.MemUsage}}' {$containerId} 2>/dev/null";
        $output = shell_exec($cmd);
        
        if (empty($output)) {
            return null;
        }
        
        $parts = explode('|', trim($output));
        if (count($parts) < 2) {
            return null;
        }
        
        return [
            'cpu' => $parts[0],
            'memory' => $parts[1],
        ];
    }

    protected function getExposedPorts(): array
    {
        $ports = [];
        
        // Read from .env
        if (File::exists(base_path('.env'))) {
            $env = File::get(base_path('.env'));
            
            $portMappings = [
                'WEB_PORT_HTTP' => 'HTTP',
                'WEB_PORT_SSL' => 'HTTPS',
                'DB_OUTER_PORT' => 'MySQL',
                'REDIS_OUTER_PORT' => 'Redis',
            ];
            
            foreach ($portMappings as $envKey => $serviceName) {
                if (preg_match("/^{$envKey}=(.*)$/m", $env, $matches)) {
                    $port = trim($matches[1]);
                    $ports[$serviceName] = $port;
                }
            }
        }
        
        // Add default URLs
        if (isset($ports['HTTPS'])) {
            $domain = $this->getDomain();
            $ports['Application'] = "https://{$domain}:{$ports['HTTPS']}";
        }
        
        return $ports;
    }

    protected function getDomain(): string
    {
        if (File::exists(base_path('.env'))) {
            $env = File::get(base_path('.env'));
            if (preg_match('/^APP_DOMAIN=(.*)$/m', $env, $matches)) {
                return trim($matches[1]);
            }
            if (preg_match('/^APP_URL=https?:\/\/(.+?)(:|\/|$)/m', $env, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return 'localhost';
    }

    protected function getServiceIcon(string $service): string
    {
        $icons = [
            'HTTP' => '🌐',
            'HTTPS' => '🔒',
            'MySQL' => '🗄️',
            'Redis' => '⚡',
            'Application' => '🚀',
        ];
        
        return $icons[$service] ?? '📍';
    }
}
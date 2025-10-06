<?php

namespace ITCompass\BasePack\Traits;

trait DisplaysLogo
{
    protected function displayLogo(): void
    {
        $logo = <<<'LOGO'
        
        ╔══════════════════════════════════════════════════════════════╗
        ║                                                              ║
        ║     ____                 ____            _                  ║
        ║    |  _ \               |  _ \          | |                 ║
        ║    | |_) | __ _ ___  ___| |_) |__ _  ___| | __              ║
        ║    |  _ < / _` / __|/ _ \  __// _` |/ __| |/ /              ║
        ║    | |_) | (_| \__ \  __/ |  | (_| | (__|   <               ║
        ║    |____/ \__,_|___/\___|_|   \__,_|\___|_|\_\              ║
        ║                                                              ║
        ║              DevOps Toolkit for Laravel Projects            ║
        ║                    by ITCompass Agency                      ║
        ║                                                              ║
        ╚══════════════════════════════════════════════════════════════╝
        
        LOGO;

        // Display logo with color
        $this->line($logo, 'info');
    }

    protected function displayCompactLogo(): void
    {
        $this->line('');
        $this->line('<fg=cyan>BasePack</> - DevOps Toolkit for Laravel Projects');
        $this->line('<fg=gray>by ITCompass Agency</>');
        $this->line('');
    }
}
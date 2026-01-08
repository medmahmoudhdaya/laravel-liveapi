<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class LiveApiStatusCommand extends Command
{
    protected $signature = 'liveapi:status';

    protected $description = 'Show the current LiveApi capture and generation status';

    public function handle(): int
    {
        $env = app()->environment();
        $storagePath = rtrim(config('liveapi.storage_path'), '/');
        $snapshotsPath = $storagePath.'/snapshots';
        $specPath = $storagePath.'/openapi.json';

        $routeCount = 0;
        $fileCount = 0;

        if (File::isDirectory($snapshotsPath)) {
            foreach (File::directories($snapshotsPath) as $dir) {
                $routeCount++;
                $fileCount += count(File::files($dir));
            }
        }

        $this->line('');
        $this->info('LiveApi Status');
        $this->line(str_repeat('-', 30));

        $this->line('Environment:      '.$env);
        $this->line('Enabled:          '.(config('liveapi.enabled', true) ? 'yes' : 'no'));
        $this->line('Frozen:           '.(config('liveapi.frozen', false) ? 'yes' : 'no'));
        $this->line('Captured routes:  '.$routeCount);
        $this->line('Snapshot files:   '.$fileCount);
        $this->line('Spec generated:   '.(File::exists($specPath) ? 'yes' : 'no'));

        $this->line('');

        if ($env === 'production') {
            $this->warn('LiveApi is hard-disabled in production.');
        }

        return self::SUCCESS;
    }
}

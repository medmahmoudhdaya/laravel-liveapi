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

        $this->newLine();
        $this->info('LiveApi Status :');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Environment', ucfirst($env)],
                ['Enabled', config('liveapi.enabled', true) ? 'Yes' : 'No'],
                ['Frozen', config('liveapi.frozen', false) ? 'Yes' : 'No'],
                ['Captured routes', $routeCount],
                ['Snapshot files', $fileCount],
                ['Spec generated', File::exists($specPath) ? 'Yes' : 'No'],
            ]
        );

        if ($env === 'production') {
            $this->newLine();
            $this->warn('LiveApi is hard-disabled in production.');
        }

        return self::SUCCESS;
    }
}

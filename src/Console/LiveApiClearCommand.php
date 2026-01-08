<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class LiveApiClearCommand extends Command
{
    protected $signature = 'liveapi:clear {--spec : Also delete the generated openapi.json file}';

    protected $description = 'Delete all captured API snapshots';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('LiveApi is hard-disabled in production.');

            return self::FAILURE;
        }

        $storagePath = rtrim(config('liveapi.storage_path'), '/');
        $snapshotsPath = $storagePath.'/snapshots';
        $specPath = $storagePath.'/openapi.json';

        if (! File::exists($snapshotsPath) && ! ($this->option('spec') && File::exists($specPath))) {
            $this->info('Nothing to clear.');

            return self::SUCCESS;
        }

        if (! $this->confirm('This will delete all captured API snapshots. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if (File::isDirectory($snapshotsPath)) {
            File::deleteDirectory($snapshotsPath);
            $this->info('Snapshots cleared.');
        }

        if ($this->option('spec') && File::exists($specPath)) {
            File::delete($specPath);
            $this->info('openapi.json deleted.');
        }

        return self::SUCCESS;
    }
}

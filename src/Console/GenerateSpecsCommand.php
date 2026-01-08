<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zidbih\LiveApi\Services\OpenApiGenerator;

final class GenerateSpecsCommand extends Command
{
    protected $signature = 'liveapi:generate {--force : Generate even if freeze mode is enabled}';

    protected $description = 'Compile captured snapshots into a single openapi.json file';

    public function handle(OpenApiGenerator $generator): int
    {
        if (app()->isProduction()) {
            $this->error('LiveApi is hard-disabled in production.');

            return self::FAILURE;
        }

        if (config('liveapi.frozen', false) && ! $this->option('force')) {
            $this->error('Freeze mode is enabled. Use --force to regenerate the spec.');

            return self::FAILURE;
        }

        $this->info('Generating OpenAPI specification...');

        $spec = $generator->generate();

        if (! is_array($spec) || empty($spec['openapi'])) {
            $this->error('Failed to generate a valid OpenAPI specification.');

            return self::FAILURE;
        }

        $outputPath = rtrim(config('liveapi.storage_path'), '/').'/openapi.json';

        File::ensureDirectoryExists(dirname($outputPath));

        File::put(
            $outputPath,
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info('Specification generated successfully at:');
        $this->line($outputPath);

        return self::SUCCESS;
    }
}

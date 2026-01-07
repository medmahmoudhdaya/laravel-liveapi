<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zidbih\LiveApi\Services\OpenApiGenerator;

final class GenerateSpecsCommand extends Command
{
    protected $signature = 'liveapi:generate';

    protected $description = 'Compile captured snapshots into a single openapi.json file';

    public function handle(OpenApiGenerator $generator): int
    {
        $this->info('Generating OpenAPI specification...');

        $spec = $generator->generate();

        $outputPath = config('liveapi.storage_path').'/openapi.json';

        File::ensureDirectoryExists(dirname($outputPath));

        File::put(
            $outputPath,
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info("Specification generated successfully at: {$outputPath}");

        return self::SUCCESS;
    }
}

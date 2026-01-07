<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Services;

use Illuminate\Support\Facades\File;

final class SchemaRepository
{
    protected string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('liveapi.storage_path');
    }

    /**
     * Records a new traffic snapshot and updates the inferred schema.
     */
    public function record(array $data): void
    {
        $this->ensureDirectoryExists();

        $key = $this->generateKey($data['method'], $data['uri']);
        $filePath = "{$this->storagePath}/snapshots/{$key}.json";

        $existingSchema = File::exists($filePath)
            ? json_decode(File::get($filePath), true)
            : [];

        $inferrer = app(SchemaInferrer::class);
        $updatedSchema = $inferrer->merge($existingSchema, $data);

        File::put($filePath, json_encode($updatedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Generates a filesystem-friendly key for the route.
     */
    protected function generateKey(string $method, string $uri): string
    {
        $normalizedUri = str_replace(['/', '{', '}'], ['-', '', ''], trim($uri, '/'));

        return strtolower("{$method}-".($normalizedUri ?: 'root'));
    }

    /**
     * Prepares the storage directories.
     */
    protected function ensureDirectoryExists(): void
    {
        if (! File::isDirectory("{$this->storagePath}/snapshots")) {
            File::makeDirectory("{$this->storagePath}/snapshots", 0755, true);
        }
    }
}

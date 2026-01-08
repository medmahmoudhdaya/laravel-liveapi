<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Services;

use Illuminate\Support\Facades\File;

final class SchemaRepository
{
    protected string $storagePath;

    public function __construct(
        protected SchemaInferrer $inferrer
    ) {
        $this->storagePath = rtrim(config('liveapi.storage_path'), '/');
    }

    /**
     * Records a new traffic snapshot and updates the inferred schema.
     */
    public function record(array $data): void
    {
        if (config('liveapi.frozen', false)) {
            return;
        }

        $this->ensureDirectoryExists();

        $auth = ! empty($data['authenticated']) ? 'authenticated' : 'guest';
        $key = $this->generateKey($data['method'], $data['uri']);

        $dir = "{$this->storagePath}/snapshots/{$key}";
        File::ensureDirectoryExists($dir);

        $filePath = "{$dir}/{$auth}.json";

        $existingSchema = File::exists($filePath)
            ? json_decode(File::get($filePath), true)
            : [];

        if (! is_array($existingSchema)) {
            $existingSchema = [];
        }

        $updatedSchema = $this->inferrer->merge($existingSchema, $data);

        $tmp = $filePath.'.tmp';
        File::put(
            $tmp,
            json_encode($updatedSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        rename($tmp, $filePath);
    }

    /**
     * Generates a collision-safe filesystem key for the route.
     */
    protected function generateKey(string $method, string $uri): string
    {
        return strtolower($method.'-'.sha1($uri));
    }

    /**
     * Prepares the storage directories.
     */
    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists("{$this->storagePath}/snapshots");
    }
}

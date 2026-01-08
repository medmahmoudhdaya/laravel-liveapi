<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Services;

use Illuminate\Support\Facades\File;

final class OpenApiGenerator
{
    public function generate(): array
    {
        $storagePath = config('liveapi.storage_path').'/snapshots';
        $config = config('liveapi.openapi');

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $config['title'],
                'version' => $config['version'],
                'description' => $config['description'],
            ],
            'servers' => [
                ['url' => $config['base_url'], 'description' => 'Current Environment'],
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => $this->getSecuritySchemes(),
            ],
        ];

        if (! File::isDirectory($storagePath)) {
            return $spec;
        }

        $files = File::files($storagePath);

        foreach ($files as $file) {
            $data = json_decode(File::get($file), true);
            if ($data) {
                $this->addPath($spec, $data);
            }
        }

        // Sort paths alphabetically for git-friendly deterministic output
        ksort($spec['paths']);

        return $spec;
    }

    protected function addPath(array &$spec, array $data): void
    {
        $uri = '/'.ltrim($data['uri'], '/');
        $method = strtolower($data['method']);
        $securityName = config('liveapi.openapi.security_scheme', 'Sanctum');

        $pathItem = [
            'summary' => "{$data['method']} {$uri}",
            'responses' => $this->formatResponses($data['responses']),
        ];

        // SENIOR FIX: Only add requestBody if there is actual schema data
        // AND it's not a GET/DELETE request (which typically don't have bodies)
        if (! empty($data['request_body']) && ! in_array($method, ['get', 'delete', 'head'], true)) {
            $pathItem['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $data['request_body'],
                    ],
                ],
            ];
        }

        if ($data['authenticated']) {
            $pathItem['security'] = [[$securityName => []]];
        }

        // Improved Tagging: Grab the first meaningful segment after /api/
        $segments = explode('/', ltrim($uri, '/'));
        $tagIndex = $segments[0] === 'api' ? 1 : 0;
        $tag = $segments[$tagIndex] ?? 'Default';

        $pathItem['tags'] = [ucfirst($tag)];

        $spec['paths'][$uri][$method] = $pathItem;
    }

    /**
     * Ensures responses are sorted by status code and formatted correctly.
     */
    protected function formatResponses(array $responses): array
    {
        ksort($responses);

        return $responses;
    }

    protected function getSecuritySchemes(): array
    {
        $name = config('liveapi.openapi.security_scheme', 'Sanctum');

        return [
            $name => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ];
    }
}

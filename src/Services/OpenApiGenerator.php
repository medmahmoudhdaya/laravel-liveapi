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
            $this->addPath($spec, $data);
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
            'responses' => $data['responses'],
        ];

        if (! empty($data['request_body'])) {
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

        $firstSegment = explode('/', ltrim($uri, '/'))[1] ?? 'default';
        $pathItem['tags'] = [ucfirst($firstSegment)];

        $spec['paths'][$uri][$method] = $pathItem;
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

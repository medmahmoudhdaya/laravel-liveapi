<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Services;

use Illuminate\Support\Facades\File;

final class OpenApiGenerator
{
    public function generate(): array
    {
        $storagePath = rtrim(config('liveapi.storage_path'), '/').'/snapshots';
        $config = config('liveapi.openapi', []);

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $config['title'] ?? 'API',
                'version' => $config['version'] ?? '1.0.0',
                'description' => $config['description'] ?? '',
            ],
            'servers' => [
                [
                    'url' => $config['base_url'] ?? 'http://localhost',
                    'description' => 'Current Environment',
                ],
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => $this->getSecuritySchemes(),
            ],
        ];

        if (! File::isDirectory($storagePath)) {
            return $spec;
        }

        foreach (File::directories($storagePath) as $routeDir) {
            foreach (File::files($routeDir) as $file) {
                $data = json_decode(File::get($file), true);

                if (! is_array($data)) {
                    continue;
                }

                $this->addPath($spec, $data);
            }
        }

        // Deterministic ordering (git-friendly)
        ksort($spec['paths']);
        foreach ($spec['paths'] as &$methods) {
            ksort($methods);
        }

        return $spec;
    }

    protected function addPath(array &$spec, array $data): void
    {
        if (
            empty($data['uri']) ||
            empty($data['method']) ||
            empty($data['responses'])
        ) {
            return;
        }

        $uri = '/'.ltrim($data['uri'], '/');
        $method = strtolower($data['method']);
        $securityName = config('liveapi.openapi.security_scheme', 'Sanctum');

        $operation = [
            'summary' => "{$data['method']} {$uri}",
            'responses' => $this->formatResponses($data['responses']),
        ];

        if (
            ! empty($data['request_body']) &&
            ! in_array($method, ['get', 'delete', 'head'], true)
        ) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $data['request_body'],
                    ],
                ],
            ];
        }

        if (! empty($data['authenticated'])) {
            $operation['security'] = [
                [$securityName => []],
            ];
        }

        // Simple deterministic tagging
        $segments = explode('/', ltrim($uri, '/'));
        $tagIndex = $segments[0] === 'api' ? 1 : 0;
        $tag = $segments[$tagIndex] ?? 'Default';

        $operation['tags'] = [ucfirst($tag)];

        $spec['paths'][$uri][$method] = $operation;
    }

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

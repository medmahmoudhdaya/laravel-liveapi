<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Zidbih\LiveApi\Services\SchemaRepository;

final class CaptureTraffic
{
    public function __construct(
        protected SchemaRepository $repository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldCapture($request, $response)) {
            return;
        }

        // Only capture body for methods that typically send payloads
        $canHaveBody = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $requestBody = $canHaveBody ? $request->json()->all() : [];

        $data = [
            'method' => $request->method(),
            'uri' => $request->route()?->uri() ?? $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'request_body' => $this->maskSensitiveData($requestBody),
            'response_body' => $this->maskSensitiveData(json_decode($response->getContent() ?: '{}', true) ?? []),
            'query_params' => $request->query(),
            'authenticated' => ! is_null($request->user()),
        ];

        $this->repository->record($data);
    }

    protected function shouldCapture(Request $request, Response $response): bool
    {
        if (config('liveapi.frozen', false) || ! config('liveapi.enabled', true)) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        if (! str_contains((string) $contentType, 'application/json')) {
            return false;
        }

        if (! $request->route()) {
            return false;
        }

        return true;
    }

    protected function maskSensitiveData(array $data): array
    {
        $masks = config('liveapi.mask_fields', []);

        if (empty($masks)) {
            return $data;
        }

        array_walk_recursive($data, function (&$value, $key) use ($masks) {
            if (in_array(strtolower((string) $key), $masks, true)) {
                $value = '*****';
            }
        });

        return $data;
    }
}

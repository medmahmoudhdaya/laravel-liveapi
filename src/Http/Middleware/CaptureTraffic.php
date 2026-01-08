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

        $requestBody = $request->isJson()
            ? $this->maskSensitiveData($request->json()->all())
            : [];

        $content = $response->getContent();
        $decoded = null;

        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                return;
            }
        }

        $data = [
            'method'        => $request->method(),
            'uri'           => $request->route()?->uri() ?? $request->getPathInfo(),
            'status'        => $response->getStatusCode(),
            'request_body'  => $requestBody,
            'response_body' => $decoded ?? [],
            'query_params'  => $this->maskSensitiveData($request->query()),
            'authenticated' => $request->user() !== null,
        ];

        $this->repository->record($data);
    }

    protected function shouldCapture(Request $request, Response $response): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        if (config('liveapi.frozen', false)) {
            return false;
        }

        if (! $request->route()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains($contentType, 'application/json');
    }

    protected function maskSensitiveData(array $data): array
    {
        $masks = array_map('strtolower', config('liveapi.mask_fields', []));

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

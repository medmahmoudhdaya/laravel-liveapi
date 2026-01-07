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

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldCapture($request, $response)) {
            return;
        }

        $data = [
            'method' => $request->method(),
            'uri' => $request->route()?->uri() ?? $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'request_body' => $this->maskSensitiveData($request->json()->all()),
            'response_body' => $this->maskSensitiveData(json_decode($response->getContent(), true) ?? []),
            'query_params' => $request->query(),
            'authenticated' => ! is_null($request->user()),
        ];

        $this->repository->record($data);
    }

    /**
     * Determine if the current request/response pair should be captured.
     */
    protected function shouldCapture(Request $request, Response $response): bool
    {
        // Avoid capturing if frozen or disabled
        if (config('liveapi.frozen', false)) {
            return false;
        }

        // Only capture JSON responses
        $contentType = $response->headers->get('Content-Type');
        if (! str_contains((string) $contentType, 'application/json')) {
            return false;
        }

        // Ensure we are within a route (avoids 404s outside the API group)
        if (! $request->route()) {
            return false;
        }

        return true;
    }

    /**
     * Recursively mask sensitive fields defined in config.
     */
    protected function maskSensitiveData(array $data): array
    {
        $masks = config('liveapi.mask_fields', []);

        array_walk_recursive($data, function (&$value, $key) use ($masks) {
            if (in_array($key, $masks, true)) {
                $value = '*****';
            }
        });

        return $data;
    }
}

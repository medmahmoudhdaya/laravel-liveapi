<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    /**
     * Display the OpenAPI dashboard.
     */
    public function index(): View
    {
        if (app()->isProduction()) {
            abort(404);
        }

        return view('liveapi::dashboard');
    }

    /**
     * Serve the generated openapi.json.
     */
    public function json(): JsonResponse
    {
        if (app()->isProduction()) {
            abort(404);
        }

        $path = rtrim(config('liveapi.storage_path'), '/').'/openapi.json';

        if (! File::exists($path)) {
            return response()->json(
                ['error' => 'Specification not found. Run php artisan liveapi:generate'],
                404
            );
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return response()->json(
                ['error' => 'Invalid OpenAPI specification file. Regenerate it.'],
                500
            );
        }

        return response()
            ->json($decoded)
            ->setPublic()
            ->setMaxAge(60);
    }
}

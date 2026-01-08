<?php

namespace Zidbih\LiveApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    /**
     * Display the Swagger UI.
     */
    public function index()
    {
        return view('liveapi::dashboard');
    }

    /**
     * Serve the generated openapi.json.
     */
    public function json(): JsonResponse
    {
        $path = config('liveapi.storage_path') . '/openapi.json';

        if (!File::exists($path)) {
            return response()->json(['error' => 'Specification not found. Run php artisan liveapi:generate'], 404);
        }

        return response()->json(json_decode(File::get($path), true));
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KetoRecipes extends Controller
{
    public function categories()
    {
        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => config('services.keto.host'),
                'x-rapidapi-key'  => config('services.keto.key'),
            ])
                ->timeout(15)
                ->get(rtrim(config('services.keto.base_url'), '/') . '/categories/');

            if (!$response->ok()) {
                return response()->json([
                    'message' => 'Keto API error',
                    'status'  => $response->status(),
                    'body'    => $response->json(),
                ], 502);
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to contact Keto API',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }

    public function recipesByCategory(Request $request)
    {
        $request->validate([
            'category' => 'required|integer|min:1',
        ]);

        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => config('services.keto.host'),
                'x-rapidapi-key'  => config('services.keto.key'),
            ])
                ->timeout(15)
                ->get(rtrim(config('services.keto.base_url'), '/'), [
                    'category' => $request->query('category'),
                ]);

            if (!$response->ok()) {
                return response()->json([
                    'message' => 'Keto API error',
                    'status'  => $response->status(),
                    'body'    => $response->json(),
                ], 502);
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to contact Keto API',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }
}
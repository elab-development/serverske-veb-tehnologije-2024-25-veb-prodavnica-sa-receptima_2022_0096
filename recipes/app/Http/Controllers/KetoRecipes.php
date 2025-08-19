<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KetoRecipes extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/keto/categories",
     *     tags={"Keto"},
     *     summary="List keto categories (RapidAPI - Keto Diet)",
     *     description="Proxies the Keto Diet API categories endpoint.",
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Breakfast"),
     *                 @OA\Property(property="slug", type="string", example="breakfast")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Upstream API error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Keto API error"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/keto/recipes",
     *     tags={"Keto"},
     *     summary="List keto recipes by category (RapidAPI - Keto Diet)",
     *     description="Proxies the Keto Diet API root endpoint with a 'category' query (e.g., /?category=2).",
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=true,
     *         description="Category ID from Keto Diet API (e.g., 2)",
     *         @OA\Schema(type="integer", minimum=1, example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="title", type="string", example="Keto Omelette"),
     *                 @OA\Property(property="description", type="string", example="Eggs, cheese..."),
     *                 @OA\Property(property="ingredients", type="string", example="eggs; cheese"),
     *                 @OA\Property(property="instructions", type="string", example="Beat eggs..."),
     *                 @OA\Property(property="image", type="string", example="https://.../image.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error (missing or invalid category)"
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Upstream API error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Keto API error"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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
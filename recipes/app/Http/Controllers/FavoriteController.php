<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
      * @OA\Get(
     *     path="/api/favorites",
     *     tags={"Favorites"},
     *     summary="List favorites for current user (admins see all)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="favorites",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Jane Doe"),
     *                         @OA\Property(property="email", type="string", example="jane@example.com")
     *                     ),
     *                     @OA\Property(property="recipe", type="object",
     *                         @OA\Property(property="id", type="integer", example=42),
     *                         @OA\Property(property="title", type="string", example="Keto Omelette"),
     *                         @OA\Property(property="description", type="string", example="Eggs, cheese...")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No favorites found.")
     * )
     */
    public function index()
    {
        $user = Auth::user();

        $favorites = $user->role === 'admin'
            ? Favorite::with(['user', 'recipe'])->get()
            : Favorite::with(['user', 'recipe'])->where('user_id', $user->id)->get();

        if ($favorites->isEmpty()) {
            return response()->json('No favorites found.', 404);
        }

        return response()->json([
            'favorites' => FavoriteResource::collection($favorites),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
      * @OA\Post(
     *     path="/api/favorites",
     *     tags={"Favorites"},
     *     summary="Add a recipe to the current user's favorites (users only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipe_id"},
     *             @OA\Property(property="recipe_id", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favorite added",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Favorite added successfully"),
     *             @OA\Property(property="favorite", type="object",
     *                 @OA\Property(property="id", type="integer", example=11),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Jane Doe")
     *                 ),
     *                 @OA\Property(property="recipe", type="object",
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="title", type="string", example="Keto Omelette")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only users can create favorites"),
     *     @OA\Response(response=409, description="Recipe already in favorites"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'user') {
            return response()->json(['error' => 'Only users can create favorites'], 403);
        }

        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
        ]);

        $existing = Favorite::where('user_id', $user->id)
            ->where('recipe_id', $validated['recipe_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Recipe already in favorites'], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'recipe_id' => $validated['recipe_id'],
        ]);

        return response()->json([
            'message' => 'Favorite added successfully',
            'favorite' => new FavoriteResource($favorite->load(['user', 'recipe'])),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Favorite $favorite)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Favorite $favorite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Favorite $favorite)
    {
        //
    }

    /**
    * @OA\Delete(
     *     path="/api/favorites/{id}",
     *     tags={"Favorites"},
     *     summary="Remove a favorite (only the owner, role=user)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true, description="Favorite ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favorite removed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Favorite removed successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only users can delete favorites / You can only delete your own favorites"),
     *     @OA\Response(response=404, description="Favorite not found")
     * )
     */
    public function destroy(Favorite $favorite)
    {
        $user = Auth::user();

        if ($user->role !== 'user') {
            return response()->json(['error' => 'Only users can delete favorites'], 403);
        }
        if ($favorite->user_id !== $user->id) {
            return response()->json(['error' => 'You can only delete your own favorites'], 403);
        }

        $favorite->delete();

        return response()->json(['message' => 'Favorite removed successfully']);
    }
}

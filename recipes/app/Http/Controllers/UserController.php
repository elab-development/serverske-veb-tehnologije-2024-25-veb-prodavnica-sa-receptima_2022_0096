<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
     /**
     * @OA\Get(
     *     path="/users/{id}/favorites",
     *     summary="Get all favorites of a specific user (Admin only)",
     *     description="Returns a list of all favorites for the given user ID. Accessible only to admins.",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of favorites for the user",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Allie Hane"),
     *                 @OA\Property(property="email", type="string", example="damore.dimitri@example.com")
     *             ),
     *             @OA\Property(
     *                 property="favorites",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Allie Hane"),
     *                         @OA\Property(property="email", type="string", example="damore.dimitri@example.com")
     *                     ),
     *                     @OA\Property(
     *                         property="recipe",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="title", type="string", example="Chicken Curry")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only admins can view user favorites",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Only admins can view user favorites")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function favorites($id, Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can view user favorites'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $favorites = $user->favorites()->with('recipe')->get();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'favorites' => FavoriteResource::collection($favorites),
        ]);
    }
}
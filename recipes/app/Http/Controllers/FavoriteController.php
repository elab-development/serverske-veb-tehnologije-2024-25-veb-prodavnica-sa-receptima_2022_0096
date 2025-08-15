<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Remove the specified resource from storage.
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

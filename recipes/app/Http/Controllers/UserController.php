<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoriteResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
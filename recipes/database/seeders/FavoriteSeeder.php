<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $recipes = Recipe::all();

        foreach ($users as $user) {
            $favoriteRecipeIds = $recipes->random(rand(2, 3))->pluck('id');

            foreach ($favoriteRecipeIds as $recipeId) {
                Favorite::firstOrCreate([
                    'user_id' => $user->id,
                    'recipe_id' => $recipeId,
                ]);
            }
        }
    }
}

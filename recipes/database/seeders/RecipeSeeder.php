<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('role', 'admin')->inRandomOrder()->first();

        $recipes = [
            [
                'title' => 'Pancakes',
                'description' => 'Fluffy homemade pancakes',
                'ingredients' => 'Flour, Milk, Eggs, Baking Powder, Sugar, Salt',
                'instructions' => 'Mix all ingredients and cook on a skillet.',
                'category' => 'Breakfast',
            ],
            [
                'title' => 'Spaghetti Bolognese',
                'description' => 'Classic Italian pasta with meat sauce',
                'ingredients' => 'Spaghetti, Ground Beef, Tomato Sauce, Garlic, Onion, Olive Oil',
                'instructions' => 'Cook pasta, prepare sauce, combine and serve.',
                'category' => 'Dinner',
            ],
            [
                'title' => 'Vegan Tacos',
                'description' => 'Healthy and tasty vegan tacos',
                'ingredients' => 'Tortillas, Black Beans, Avocado, Tomato, Onion, Cilantro',
                'instructions' => 'Assemble all ingredients in tortillas.',
                'category' => 'Vegan',
            ],
            [
                'title' => 'Chicken Curry',
                'description' => 'A spicy and creamy Indian-style chicken curry.',
                'ingredients' => 'Chicken, Onion, Garlic, Ginger, Tomatoes, Coconut Milk, Curry Powder',
                'instructions' => 'Cook onions, garlic, and ginger; add chicken and spices; simmer in coconut milk.',
                'category' => 'Dinner',
            ],
            [
                'title' => 'Avocado Toast',
                'description' => 'A quick and healthy breakfast option.',
                'ingredients' => 'Bread, Avocado, Lemon Juice, Salt, Pepper, Chili Flakes',
                'instructions' => 'Toast bread, mash avocado with lemon, spread on toast, sprinkle toppings.',
                'category' => 'Breakfast',
            ],
            [
                'title' => 'Chocolate Cake',
                'description' => 'Moist and rich chocolate layer cake.',
                'ingredients' => 'Flour, Cocoa Powder, Eggs, Sugar, Baking Powder, Butter, Milk',
                'instructions' => 'Mix ingredients, bake in a preheated oven at 180°C for 30 minutes.',
                'category' => 'Dessert',
            ],
            [
                'title' => 'Quinoa Salad',
                'description' => 'A fresh and healthy gluten-free salad.',
                'ingredients' => 'Quinoa, Cucumber, Tomato, Lemon, Olive Oil, Mint, Salt',
                'instructions' => 'Cook quinoa, chop veggies, mix with lemon juice and olive oil.',
                'category' => 'Gluten-Free',
            ],
            [
                'title' => 'Banana Smoothie',
                'description' => 'A creamy smoothie packed with energy.',
                'ingredients' => 'Banana, Milk, Honey, Ice Cubes, Chia Seeds',
                'instructions' => 'Blend all ingredients until smooth.',
                'category' => 'Vegan',
            ],
        ];

        foreach ($recipes as $data) {
            $category = Category::where('name', $data['category'])->first();

            Recipe::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'ingredients' => $data['ingredients'],
                'instructions' => $data['instructions'],
            ]);
        }
    }
}

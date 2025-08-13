<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        User::factory(5)->create([
            'role' => 'user'
        ]);

        // Seed data
        $this->call([
            CategorySeeder::class,
            RecipeSeeder::class,
            FavoriteSeeder::class,
        ]);
    }
}

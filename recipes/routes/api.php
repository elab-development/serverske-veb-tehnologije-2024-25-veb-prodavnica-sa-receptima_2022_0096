<?php
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KetoRecipes;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RecipeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/search', [RecipeController::class, 'search']);
Route::get('/recipes/{id}', [RecipeController::class, 'show']);

Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/login', [AuthenticationController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    
    Route::get("/keto/categories", [KetoRecipes::class, 'categories']);
    Route::get("/keto/recipes", [KetoRecipes::class, 'recipesByCategory']);

    Route::resource('categories', CategoryController::class)
    ->only(['store', 'update', 'destroy']);

    Route::resource('recipes', RecipeController::class)
    ->only(['store', 'update', 'destroy']);



    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy']);



    Route::post('/logout', [AuthenticationController::class, 'logout']);
});
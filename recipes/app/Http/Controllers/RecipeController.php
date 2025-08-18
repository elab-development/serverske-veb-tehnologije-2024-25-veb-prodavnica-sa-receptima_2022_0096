<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/recipes",
     *     tags={"Recipes"},
     *     summary="List recipes with filters and pagination",
     *     @OA\Parameter(name="title", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="description", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="ingredients", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="instructions", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", description="Category name contains", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="recipes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Keto Omelette"),
     *                     @OA\Property(property="description", type="string", example="Eggs, cheese..."),
     *                     @OA\Property(property="ingredients", type="string", example="eggs; cheese"),
     *                     @OA\Property(property="instructions", type="string", example="Beat eggs..."),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Breakfast")
     *                     ),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Jane Doe"),
     *                         @OA\Property(property="email", type="string", example="jane@example.com")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Recipe::with(['user', 'category']);

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->query('title') . '%');
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->query('description') . '%');
        }

        if ($request->filled('ingredients')) {
            $query->where('ingredients', 'like', '%' . $request->query('ingredients') . '%');
        }

        if ($request->filled('instructions')) {
            $query->where('instructions', 'like', '%' . $request->query('instructions') . '%');
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query('category') . '%');
            });
        }

        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $recipes = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/recipes/search",
     *     tags={"Recipes"},
     *     summary="Search recipes by general query with optional sorting and pagination",
     *     @OA\Parameter(
     *         name="query", in="query", required=true,
     *         description="Search across title, description, ingredients, instructions, and category name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_title", in="query",
     *         description="Sort by title (asc|desc)",
     *         @OA\Schema(type="string", enum={"asc","desc"})
     *     ),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="recipes", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="title", type="string", example="Grilled Chicken Salad"),
     *                     @OA\Property(property="description", type="string", example="Low carb salad..."),
     *                     @OA\Property(property="ingredients", type="string"),
     *                     @OA\Property(property="instructions", type="string"),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Lunch")
     *                     ),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=7),
     *                         @OA\Property(property="name", type="string", example="John Smith")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Search query is required"),
     *     @OA\Response(response=404, description="No matching recipes found")
     * )
     */
    public function search(Request $request)
    {
        $q = $request->query('query');
        if (!$q) {
            return response()->json(['error' => 'Search query is required.'], 400);
        }

        $builder = Recipe::with(['user', 'category'])
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('ingredients', 'like', "%{$q}%")
                    ->orWhere('instructions', 'like', "%{$q}%")
                    ->orWhereHas('category', function ($sub) use ($q) {
                        $sub->where('name', 'like', "%{$q}%");
                    });
            });

        $sort = strtolower($request->query('sort_title', ''));
        if (in_array($sort, ['asc', 'desc'], true)) {
            $builder->orderBy('title', $sort);
        }

        $perPage = (int) $request->query('per_page', 10);
        $page    = (int) $request->query('page', 1);

        $recipes = $builder->paginate($perPage, ['*'], 'page', $page);

        if ($recipes->isEmpty()) {
            return response()->json(['message' => 'No matching recipes found.'], 404);
        }

        return response()->json([
            'recipes' => RecipeResource::collection($recipes),
        ]);
    }
   
    /**
     * @OA\Get(
     *     path="/api/recipes/export-csv",
     *     tags={"Recipes"},
     *     summary="Export all recipes as CSV (streamed download)",
     *     @OA\Response(
     *         response=200,
     *         description="CSV stream",
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="attachment; filename=recipes_YYYYMMDD_HHMMSS.csv",
     *             @OA\Schema(type="string")
     *         ),
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
     public function exportCsv()
    {
        $filename = 'recipes_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ID', 'Title', 'Description', 'Ingredients', 'Instructions', 'Category', 'Author', 'Author Email', 'Created At']);

            Recipe::with(['category:id,name', 'user:id,name,email'])
                ->select(['id', 'title', 'description', 'ingredients', 'instructions', 'category_id', 'user_id', 'created_at'])
                ->orderBy('id')
                ->chunk(1000, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->id,
                            $r->title,
                            $r->description,
                            $r->ingredients,
                            $r->instructions,
                            optional($r->category)->name,
                            optional($r->user)->name,
                            optional($r->user)->email,
                            $r->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
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
     *     path="/api/recipes",
     *     tags={"Recipes"},
     *     summary="Create a recipe (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","ingredients","instructions"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Keto Pancakes"),
     *             @OA\Property(property="description", type="string", example="Light and fluffy..."),
     *             @OA\Property(property="ingredients", type="string", example="almond flour; eggs; butter"),
     *             @OA\Property(property="instructions", type="string", example="Mix ingredients..."),
     *             @OA\Property(property="category_id", type="integer", nullable=true, example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recipe created successfully"),
     *             @OA\Property(property="recipe", type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="title", type="string", example="Keto Pancakes")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can create recipes"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can create recipes'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $recipe = Recipe::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Recipe created successfully',
            'recipe' => new RecipeResource($recipe->load(['user', 'category'])),
        ]);
    }

    /**
      @OA\Get(
     *     path="/api/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Get a recipe by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="recipe",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="title", type="string", example="Keto Lasagna"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="ingredients", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", example=4),
     *                     @OA\Property(property="name", type="string", example="Dinner")
     *                 ),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Chef Anna")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recipe not found")
     * )
     */
    public function show($id)
    {
        $recipe = Recipe::with(['user', 'category'])->find($id);

        if (!$recipe) {
            return response()->json('Recipe not found', 404);
        }

        return response()->json([
            'recipe' => new RecipeResource($recipe),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Update a recipe (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="ingredients", type="string"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="category_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recipe updated successfully"),
     *             @OA\Property(property="recipe", type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="title", type="string", example="Updated Title")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can update recipes"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Recipe $recipe)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can update recipes'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'sometimes|string',
            'instructions' => 'sometimes|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $recipe->update($validated);

        return response()->json([
            'message' => 'Recipe updated successfully',
            'recipe' => new RecipeResource($recipe->fresh('user', 'category')),
        ]);
    }

    /**
    * @OA\Delete(
     *     path="/api/recipes/{id}",
     *     tags={"Recipes"},
     *     summary="Delete a recipe (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Recipe deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Recipe deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can delete recipes")
     * )
     */
    public function destroy(Recipe $recipe)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can delete recipes'], 403);
        }

        $recipe->delete();

        return response()->json(['message' => 'Recipe deleted successfully']);
    }
}

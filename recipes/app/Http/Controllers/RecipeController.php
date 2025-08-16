<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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

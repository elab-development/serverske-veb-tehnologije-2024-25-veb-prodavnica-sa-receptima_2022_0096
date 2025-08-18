<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Http\Resources\RecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="List all categories",
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Breakfast")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No categories found.")
     * )
     */
    public function index()
    {
        $categories = Category::all();
        if ($categories->isEmpty()) {
            return response()->json('No categories found.', 404);
        }

        return response()->json([
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}/recipes",
     *     tags={"Categories"},
     *     summary="List recipes for a given category",
     *     @OA\Parameter(
     *         name="id", in="path", required=true, description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="category", type="string", example="Breakfast"),
     *             @OA\Property(
     *                 property="recipes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="title", type="string", example="Keto Omelette"),
     *                     @OA\Property(property="description", type="string", example="Eggs, cheese..."),
     *                     @OA\Property(property="ingredients", type="string", example="eggs; cheese"),
     *                     @OA\Property(property="instructions", type="string", example="Beat eggs..."),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name", type="string", example="Jane Doe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
     public function recipes($id)
    {
        $category = Category::with('recipes.user')
            ->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json([
            'category' => $category->name,
            'recipes' => RecipeResource::collection($category->recipes)
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
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="Create a new category (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Dessert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Dessert")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can create categories"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can create categories'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => new CategoryResource($category),
        ]);
    }

    /**
      * @OA\Get(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get a single category",
     *     @OA\Parameter(
     *         name="id", in="path", required=true, description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="category",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Lunch")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show($id)
    {
        $category = Category::find($id);
        if (is_null($category)) {
            return response()->json('Category not found', 404);
        }

        return response()->json([
            'category' => new CategoryResource($category),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update a category (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true, description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Dinner")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Dinner")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can update categories"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Category $category)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can update categories'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => new CategoryResource($category),
        ]);
    }

    /**
    * @OA\Delete(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete a category (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true, description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Only admins can delete categories")
     * )
     */
    public function destroy(Category $category)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can delete categories'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}

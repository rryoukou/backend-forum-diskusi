<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: "/categories",
        summary: "Display a listing of categories",
        tags: ["2. POSTINGAN"],
        responses: [
            new OA\Response(response: 200, description: "List of categories", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object")))
        ]
    )]
    public function index()
    {
        $categories = Cache::remember('categories_list', now()->addDay(), function () {
            return Category::with('children')->whereNull('parent_id')->get();
        });

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:120|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:categories,id',
        ]);

        $category = Category::create($request->all());

        Cache::forget('categories_list');

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'string|max:100',
            'slug' => 'string|max:120|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:categories,id',
        ]);

        $category->update($request->all());

        Cache::forget('categories_list');

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Prevent deletion if it has children or posts? 
        // For now, let's just delete. The migration says onDelete('set null') for parent_id.
        // But for posts, we might want to prevent deletion or reassign them.
        
        $category->delete();

        Cache::forget('categories_list');

        return response()->json(null, 204);
    }
}

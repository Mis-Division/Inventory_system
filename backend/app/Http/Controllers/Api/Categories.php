<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
class Categories extends Controller
{
    public function CreateCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500', 
        ]);
        DB::beginTransaction();
        try {
            $category = Category::create([
                'category_name' => $request->category_name,
                'description' => $request->description,
            ]);
            DB::commit();
            return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create category', 'message' => $e->getMessage()], 500);
        }
    }
    public function GetCategories()
    {
       try{
              $categories = Category::all();
              return response()->json(['categories' => $categories], 200);
         } catch (\Exception $e) {
              return response()->json(['error' => 'Failed to fetch categories', 'message' => $e->getMessage()], 500);
       }
    }
    public function GetCategoryById($id)
    {
       try{
              $category = Category::find($id);
              if (!$category) {
                  return response()->json(['error' => 'Category not found'], 404);
              }
              return response()->json(['category' => $category], 200);
         } catch (\Exception $e) {
              return response()->json(['error' => 'Failed to fetch category', 'message' => $e->getMessage()], 500);
       }
    }
    public function UpdateCategory(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
            $category->update($request->only(['category_name', 'description']));
            DB::commit();
            return response()->json(['message' => 'Category updated successfully', 'category' => $category], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update category', 'message' => $e->getMessage()], 500);
        }
    }
    public function DeleteCategory($id)
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
            $category->delete();
            DB::commit();
            return response()->json(['message' => 'Category deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete category', 'message' => $e->getMessage()], 500);
        }
    }
}

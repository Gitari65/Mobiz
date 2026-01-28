<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $categories = ProductCategory::where(function($query) use ($user) {
            $query->whereNull('company_id')
                  ->orWhere('company_id', $user->company_id);
        })
        ->with(['creator', 'editor', 'company'])
        ->orderBy('company_id', 'asc')
        ->orderBy('name', 'asc')
        ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $user->load('role');
            $roleName = strtolower($user->role->name ?? '');
            if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
                return response()->json(['error' => 'Only administrators can create categories'], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'icon' => 'nullable|string|max:100',
            ]);

            $validated['company_id'] = $user->company_id;
            $validated['created_by'] = $user->id;
            $validated['updated_by'] = $user->id;

            $category = ProductCategory::create($validated);
            $category->load(['creator', 'editor', 'company']);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Invalid input', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create category: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create category'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $category = ProductCategory::findOrFail($id);

            $user->load('role');
            $roleName = strtolower($user->role->name ?? '');
            if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
                return response()->json(['error' => 'Only administrators can edit categories'], 403);
            }

            if ($category->company_id === null) {
                return response()->json(['error' => 'System default categories cannot be edited'], 403);
            }

            if ($roleName !== 'superuser' && $category->company_id !== $user->company_id) {
                return response()->json(['error' => 'You can only edit categories from your company'], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string|max:1000',
                'icon' => 'sometimes|nullable|string|max:100',
            ]);

            $validated['updated_by'] = $user->id;

            $category->update($validated);
            $category->load(['creator', 'editor', 'company']);

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ]);

        } catch (\Exception $e) {
            Log::error("Error updating category {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update category'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $category = ProductCategory::findOrFail($id);

            $user->load('role');
            $roleName = strtolower($user->role->name ?? '');
            if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
                return response()->json(['error' => 'Only administrators can delete categories'], 403);
            }

            if ($category->company_id === null) {
                return response()->json(['error' => 'System default categories cannot be deleted'], 403);
            }

            if ($roleName !== 'superuser' && $category->company_id !== $user->company_id) {
                return response()->json(['error' => 'You can only delete categories from your company'], 403);
            }

            $category->delete();

            return response()->json(['message' => 'Category deleted successfully']);

        } catch (\Exception $e) {
            Log::error("Error deleting category {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete category'], 500);
        }
    }

    public function bulkUpload(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $user->load('role');
            $roleName = strtolower($user->role->name ?? '');
            if (!in_array($roleName, ['admin', 'administrator', 'superuser'])) {
                return response()->json(['error' => 'Only administrators can upload categories'], 403);
            }

            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:2048',
            ]);

            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($csvData);

            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($csvData as $row) {
                if (count($row) < 1 || empty($row[0])) {
                    $skipped++;
                    continue;
                }

                try {
                    $categoryData = [
                        'name' => $row[0],
                        'description' => $row[1] ?? null,
                        'icon' => $row[2] ?? null,
                        'company_id' => $user->company_id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ];

                    ProductCategory::create($categoryData);
                    $created++;

                } catch (\Exception $e) {
                    $errors[] = "Row with name '{$row[0]}': " . $e->getMessage();
                    $skipped++;
                }
            }

            return response()->json([
                'message' => "Bulk upload complete. Created: {$created}, Skipped: {$skipped}",
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Bulk upload failed'], 500);
        }
    }
}

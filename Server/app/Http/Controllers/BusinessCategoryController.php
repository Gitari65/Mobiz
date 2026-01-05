<?php
namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use Illuminate\Http\Request;

class BusinessCategoryController extends Controller
{
    // GET /api/business-categories - list all categories
    public function index()
    {
        $categories = BusinessCategory::orderBy('name')->get();
        return response()->json(['categories' => $categories, 'data' => $categories]);
    }
}

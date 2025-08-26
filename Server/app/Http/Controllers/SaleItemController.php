<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleItemController extends Controller
{
    public function index()
    {
        try {
            $items = SaleItem::all();
            return response()->json($items);
        } catch (\Exception $e) {
            Log::error('SaleItemController@index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sale items'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $item = SaleItem::create($request->all());
            return response()->json($item, 201);
        } catch (\Exception $e) {
            Log::error('SaleItemController@store error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to store sale item'], 500);
        }
    }

    public function show($id)
    {
        try {
            $item = SaleItem::findOrFail($id);
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error("SaleItemController@show error: {$e->getMessage()}", [
                'sale_item_id' => $id
            ]);
            return response()->json(['error' => 'Sale item not found'], 404);
        }
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return response()->json([
            'warehouses' => Warehouse::with('users')->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'user_id' => 'nullable|exists:users,id',
        ]);
        return Warehouse::create($data);
    }

    public function show($id)
    {
        $warehouse = Warehouse::with('users', 'products', 'company')->findOrFail($id);
        return response()->json($warehouse);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        
        $warehouse->update($data);
        return response()->json($warehouse);
    }

    public function destroy($id)
    {
        Warehouse::destroy($id);
        return response()->noContent();
    }
}

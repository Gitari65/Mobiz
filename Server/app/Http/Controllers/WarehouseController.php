<?php
namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return Warehouse::with('users')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);
        return Warehouse::create($data);
    }

    public function destroy($id)
    {
        Warehouse::destroy($id);
        return response()->noContent();
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return PaymentMethod::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'mpesa_type' => 'nullable|in:till,paybill',
            'mpesa_number' => 'nullable|string',
        ]);
        return PaymentMethod::create($data);
    }

    public function destroy($id)
    {
        PaymentMethod::destroy($id);
        return response()->noContent();
    }
}

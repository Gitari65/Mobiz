<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // Products
    Route::apiResource('products', ProductController::class);
    Route::post('products/bulk', [ProductController::class, 'bulkCreate']);
    Route::post('products/csv-upload', [ProductController::class, 'csvUpload']);
    
    // Sales
    Route::apiResource('sales', SaleController::class);
    Route::get('sales/stats/dashboard', [SaleController::class, 'getDashboardStats']);
    
    // Expenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::get('expenses/categories', [ExpenseController::class, 'getCategories']);
    Route::get('expenses/payment-methods', [ExpenseController::class, 'getPaymentMethods']);
    Route::get('expenses/dashboard', [ExpenseController::class, 'getDashboard']);
    Route::get('expenses/profit-loss', [ExpenseController::class, 'getProfitLoss']);
    Route::get('expenses/export', [ExpenseController::class, 'export']);
    Route::patch('expenses/{expense}/approve', [ExpenseController::class, 'approve']);
    Route::patch('expenses/{expense}/reject', [ExpenseController::class, 'reject']);
    Route::patch('expenses/{expense}/mark-paid', [ExpenseController::class, 'markAsPaid']);
    
    // Inventory
    Route::post('inventory/restock', function (Request $request) {
        // Handle inventory restock
        return response()->json(['success' => true, 'message' => 'Inventory restocked successfully']);
    });
});

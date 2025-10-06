
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CompanyController;

//company 

Route::post('/register-company', [CompanyController::class, 'registerCompany']);
Route::get('/companies/pending', [CompanyController::class, 'index']);
Route::put('/companies/{id}/approve', [CompanyController::class, 'approve']);
Route::delete('/companies/{id}/reject', [CompanyController::class, 'reject']);



// AUTHENTICATION & USER MANAGEMENT
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-user/{id}', [AuthController::class, 'verifyUser']); // Superuser only
Route::get('/unverified-users', [AuthController::class, 'unverifiedUsers']); // Superuser only
// SUPER USER: Company management
Route::get('/companies', [AuthController::class, 'listCompanies']);
Route::patch('/companies/{id}/activate', [AuthController::class, 'activateCompany']);
Route::patch('/companies/{id}/deactivate', [AuthController::class, 'deactivateCompany']);

// PRODUCTS
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/statistics', [ProductController::class, 'getStatistics']);
Route::get('/products/out-of-stock', [ProductController::class, 'getOutOfStockProducts']);
Route::get('/products/low-stock', [ProductController::class, 'getLowStockProducts']);
Route::get('/products/csv-template', [ProductController::class, 'downloadCSVTemplate']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::middleware(['role:admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/bulk', [ProductController::class, 'storeBulk']);
    Route::post('/products/import-csv', [ProductController::class, 'importCSV']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    // Business Categories (admin only)
    Route::post('/business-categories', [ProductController::class, 'storeCategory']);
    Route::put('/business-categories/{id}', [ProductController::class, 'updateCategory']);
    Route::delete('/business-categories/{id}', [ProductController::class, 'destroyCategory']);

    // Warehouses (admin only)
    Route::post('/warehouses', [ProductController::class, 'storeWarehouse']);
    Route::put('/warehouses/{id}', [ProductController::class, 'updateWarehouse']);
    Route::delete('/warehouses/{id}', [ProductController::class, 'destroyWarehouse']);
});

// SALES
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/sales', [SaleController::class, 'index']);
Route::get('/sales/{id}', [SaleController::class, 'show']);

// DASHBOARD
Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);

// PURCHASES
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::get('/purchases', [PurchaseController::class, 'index']);

// REPORTS
Route::prefix('reports')->group(function () {
    Route::get('/sales-overview', [ReportController::class, 'salesOverview']);
    Route::get('/inventory-summary', [ReportController::class, 'inventorySummary']);
    Route::get('/top-selling-products', [ReportController::class, 'topSellingProducts']);
});

// EXPENSES
Route::get('/expenses', [ExpenseController::class, 'index']);
Route::get('/expenses/categories', [ExpenseController::class, 'getCategories']);
Route::get('/expenses/payment-methods', [ExpenseController::class, 'getPaymentMethods']);
Route::get('/expenses/dashboard', [ExpenseController::class, 'getDashboard']);
Route::get('/expenses/profit-loss', [ExpenseController::class, 'getProfitLoss']);
Route::get('/expenses/export', [ExpenseController::class, 'export']);
Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
Route::post('/expenses', [ExpenseController::class, 'store']);
Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
Route::patch('/expenses/{id}/approve', [ExpenseController::class, 'approve']);
Route::patch('/expenses/{id}/reject', [ExpenseController::class, 'reject']);
Route::patch('/expenses/{id}/mark-paid', [ExpenseController::class, 'markAsPaid']);

// Test route for expense system
Route::get('/test-expenses', function () {
    try {
        $expenses = App\Models\Expense::all();
        return response()->json([
            'status' => 'success',
            'count' => $expenses->count(),
            'expenses' => $expenses
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

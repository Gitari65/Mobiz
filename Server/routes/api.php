use App\Http\Controllers\ImpersonateController;
    // Super User: Impersonate Business Admin
    Route::get('superuser/businesses', [ImpersonateController::class, 'businesses']);
    Route::post('superuser/impersonate/{businessId}', [ImpersonateController::class, 'impersonate']);
use App\Http\Controllers\SubscriptionController;
    // Super User: Subscription & Billing
    Route::get('superuser/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('superuser/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::post('superuser/subscriptions/{id}/activate', [SubscriptionController::class, 'activate']);
    Route::post('superuser/subscriptions/{id}/deactivate', [SubscriptionController::class, 'deactivate']);
use App\Http\Controllers\SupportController;
    // Super User: Support & Communication
    Route::get('superuser/support-tickets', [SupportController::class, 'tickets']);
    Route::post('superuser/support-tickets/{id}/reply', [SupportController::class, 'reply']);
use App\Http\Controllers\DataExportController;
    // Super User: Data Export
    Route::get('superuser/export/businesses', [DataExportController::class, 'exportBusinesses']);
    Route::get('superuser/export/users', [DataExportController::class, 'exportUsers']);
use App\Http\Controllers\GlobalSettingsController;
    // Super User: Global Settings
    Route::get('superuser/business-categories', [GlobalSettingsController::class, 'listCategories']);
    Route::post('superuser/business-categories', [GlobalSettingsController::class, 'addCategory']);
    Route::delete('superuser/business-categories/{name}', [GlobalSettingsController::class, 'removeCategory']);
    Route::get('superuser/feature-toggles', [GlobalSettingsController::class, 'listFeatures']);
    Route::post('superuser/feature-toggles', [GlobalSettingsController::class, 'setFeature']);
    Route::get('superuser/announcement', [GlobalSettingsController::class, 'getAnnouncement']);
    Route::post('superuser/announcement', [GlobalSettingsController::class, 'setAnnouncement']);
use App\Http\Controllers\AuditLogController;
    // Super User: Audit Logs
    Route::get('superuser/audit-logs', [AuditLogController::class, 'index']);
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

use App\Http\Controllers\SuperUserController;

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
    // Super User: User Management
    Route::get('superuser/users', [SuperUserController::class, 'users']);
    Route::post('superuser/users/{id}/activate', [SuperUserController::class, 'activateUser']);
    Route::post('superuser/users/{id}/deactivate', [SuperUserController::class, 'deactivateUser']);
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SuperUserController;
use App\Http\Controllers\SuperUser\UserController;
use App\Http\Controllers\SuperUser\ChatController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PrinterSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // Products (auth required so $request->user() is available)
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk', [ProductController::class, 'bulkCreate']);
        Route::post('products/csv-upload', [ProductController::class, 'csvUpload']);
        Route::get('products/{product}/price-for-group/{groupId}', [ProductController::class, 'getPriceForGroup']);
        Route::get('products/{product}/price-for-customer/{customerId}', [ProductController::class, 'getPriceForCustomer']);
        
        // Product Empties/Returnables
        Route::get('products/{product}/empties', [ProductController::class, 'getEmpties']);
        Route::get('products/{product}/available-empties', [ProductController::class, 'getAvailableEmpties']);
        Route::post('products/{product}/empties', [ProductController::class, 'linkEmpty']);
        Route::put('products/{product}/empties/{empty}', [ProductController::class, 'updateEmpty']);
        Route::delete('products/{product}/empties/{empty}', [ProductController::class, 'unlinkEmpty']);
    });
    
    // Sales (auth required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('sales', SaleController::class);
        Route::get('sales/stats/dashboard', [SaleController::class, 'getDashboardStats']);
    });
    
    // Credit Management
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('customers/{customerId}/credit', [\App\Http\Controllers\CreditController::class, 'history']);
        Route::post('customers/{customerId}/credit/payment', [\App\Http\Controllers\CreditController::class, 'recordPayment']);
        Route::put('customers/{customerId}/credit/limit', [\App\Http\Controllers\CreditController::class, 'updateCreditLimit']);
        Route::post('customers/{customerId}/credit/adjust', [\App\Http\Controllers\CreditController::class, 'adjustBalance']);
    });
    
    // Invoices
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('invoices', \App\Http\Controllers\InvoiceController::class);
        Route::post('invoices/{invoice}/payment', [\App\Http\Controllers\InvoiceController::class, 'recordPayment']);
    });
    
    // Returns
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('returns', \App\Http\Controllers\ReturnController::class);
        Route::post('returns/{return}/approve', [\App\Http\Controllers\ReturnController::class, 'approve']);
        Route::post('returns/{return}/reject', [\App\Http\Controllers\ReturnController::class, 'reject']);
        Route::post('returns/{return}/complete', [\App\Http\Controllers\ReturnController::class, 'complete']);
    });
    
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

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/printer-settings', [PrinterSettingsController::class, 'show']);
        Route::put('/printer-settings', [PrinterSettingsController::class, 'update']);
    });
});

// Company (Admin) - authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('company/me', [\App\Http\Controllers\CompanyController::class, 'myCompany']);
    Route::put('company/me', [\App\Http\Controllers\CompanyController::class, 'updateMyCompany']);
    Route::get('company/subscription', [\App\Http\Controllers\CompanyController::class, 'mySubscription']);
});

// Super User: Impersonate Business Admin
Route::get('superuser/businesses', [ImpersonateController::class, 'businesses']);
Route::post('superuser/impersonate/{businessId}', [ImpersonateController::class, 'impersonate']);

// Super User: Subscription & Billing
Route::get('superuser/subscriptions', [SubscriptionController::class, 'index']);
Route::post('superuser/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);
Route::post('superuser/subscriptions/{id}/activate', [SubscriptionController::class, 'activate']);
Route::post('superuser/subscriptions/{id}/deactivate', [SubscriptionController::class, 'deactivate']);

// Super User: Support & Communication
Route::get('superuser/support-tickets', [SupportController::class, 'tickets']);
Route::post('superuser/support-tickets/{id}/reply', [SupportController::class, 'reply']);

// Super User: Data Export
Route::get('superuser/export/businesses', [DataExportController::class, 'exportBusinesses']);
Route::get('superuser/export/users', [DataExportController::class, 'exportUsers']);

// Super User: Global Settings
Route::get('superuser/business-categories', [GlobalSettingsController::class, 'listCategories']);
Route::post('superuser/business-categories', [GlobalSettingsController::class, 'addCategory']);
Route::delete('superuser/business-categories/{name}', [GlobalSettingsController::class, 'removeCategory']);
Route::get('superuser/feature-toggles', [GlobalSettingsController::class, 'listFeatures']);
Route::post('superuser/feature-toggles', [GlobalSettingsController::class, 'setFeature']);
Route::get('superuser/announcement', [GlobalSettingsController::class, 'getAnnouncement']);
Route::post('superuser/announcement', [GlobalSettingsController::class, 'setAnnouncement']);

// Super User: Audit Logs
Route::get('superuser/audit-logs', [AuditLogController::class, 'index']);

// Super User: Companies Management
Route::get('superuser/companies', [CompanyController::class, 'index']);
Route::post('superuser/companies', [CompanyController::class, 'store']);
Route::put('superuser/companies/{id}', [CompanyController::class, 'update']);
Route::delete('superuser/companies/{id}', [CompanyController::class, 'destroy']);

// Super User: User Management
Route::middleware(['auth:sanctum', 'role:superuser'])->group(function () {
    Route::apiResource('super/users', UserController::class);
    Route::patch('super/users/{id}/activate', [UserController::class, 'activate']);
    Route::patch('super/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('super/users/{id}/reset-password', [UserController::class, 'resetPassword']);
});

// Admin Customization (Business Settings)
Route::get('business-categories', [GlobalSettingsController::class, 'listCategories']);
Route::post('business-categories', [GlobalSettingsController::class, 'addCategory']);
Route::delete('business-categories/{name}', [GlobalSettingsController::class, 'removeCategory']);

// Product Categories
Route::apiResource('product-categories', \App\Http\Controllers\ProductCategoryController::class);

// Warehouses
Route::apiResource('warehouses', \App\Http\Controllers\WarehouseController::class);

// Payment Methods
Route::get('payment-methods/enabled', [\App\Http\Controllers\PaymentMethodController::class, 'enabled']);
Route::apiResource('payment-methods', \App\Http\Controllers\PaymentMethodController::class);

// Roles and Companies for superuser interface
Route::get('roles', [RoleController::class, 'index']);
Route::get('companies', [CompanyController::class, 'getCompanies']);


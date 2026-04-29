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
use App\Http\Controllers\UOMController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\MpesaController;

use App\Http\Controllers\SuperUser\SubscriptionController as SuperSubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // Products (auth required + inventory feature)
    Route::middleware(['auth:sanctum', 'feature:inventory'])->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk', [ProductController::class, 'storeBulk']);
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
    
    // Sales (auth required + sales feature)
    Route::middleware(['auth:sanctum', 'feature:sales'])->group(function () {
        Route::apiResource('sales', SaleController::class);
        Route::get('sales/stats/dashboard', [SaleController::class, 'getDashboardStats']);
    });
    
    // Credit Management (auth required + credit feature)
    Route::middleware(['auth:sanctum', 'feature:credit'])->group(function () {
        Route::get('customers/{customerId}/credit', [\App\Http\Controllers\CreditController::class, 'history']);
        Route::post('customers/{customerId}/credit/payment', [\App\Http\Controllers\CreditController::class, 'recordPayment']);
        Route::put('customers/{customerId}/credit/limit', [\App\Http\Controllers\CreditController::class, 'updateCreditLimit']);
        Route::post('customers/{customerId}/credit/adjust', [\App\Http\Controllers\CreditController::class, 'adjustBalance']);
    });
    
    // Invoices (auth required + invoicing feature)
    Route::middleware(['auth:sanctum', 'feature:invoicing'])->group(function () {
        Route::apiResource('invoices', \App\Http\Controllers\InvoiceController::class);
        Route::post('invoices/{invoice}/payment', [\App\Http\Controllers\InvoiceController::class, 'recordPayment']);
        Route::post('invoices/{invoice}/reverse', [\App\Http\Controllers\InvoiceController::class, 'reverse']);
    });
    
    // Returns (auth required + returns feature)
    Route::middleware(['auth:sanctum', 'feature:returns'])->group(function () {
        Route::apiResource('returns', \App\Http\Controllers\ReturnController::class);
        Route::post('returns/{return}/approve', [\App\Http\Controllers\ReturnController::class, 'approve']);
        Route::post('returns/{return}/reject', [\App\Http\Controllers\ReturnController::class, 'reject']);
        Route::post('returns/{return}/complete', [\App\Http\Controllers\ReturnController::class, 'complete']);
    });
    
    // Expenses (auth required + expenses feature)
    Route::middleware(['auth:sanctum', 'feature:expenses'])->group(function () {
        Route::apiResource('expenses', ExpenseController::class);
        Route::get('expenses/categories', [ExpenseController::class, 'getCategories']);
        Route::get('expenses/payment-methods', [ExpenseController::class, 'getPaymentMethods']);
        Route::get('expenses/dashboard', [ExpenseController::class, 'getDashboard']);
        Route::get('expenses/profit-loss', [ExpenseController::class, 'getProfitLoss']);
        Route::get('expenses/export', [ExpenseController::class, 'export']);
        Route::patch('expenses/{expense}/approve', [ExpenseController::class, 'approve']);
        Route::patch('expenses/{expense}/reject', [ExpenseController::class, 'reject']);
        Route::patch('expenses/{expense}/mark-paid', [ExpenseController::class, 'markAsPaid']);
    });
    
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
        Route::post('/printer-settings/logo', [PrinterSettingsController::class, 'uploadLogo']);
        Route::delete('/printer-settings/logo', [PrinterSettingsController::class, 'removeLogo']);
    });
});

// Company (Admin) - authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('company/me', [\App\Http\Controllers\CompanyController::class, 'myCompany']);
    Route::put('company/me', [\App\Http\Controllers\CompanyController::class, 'updateMyCompany']);
    Route::get('company/subscription', [\App\Http\Controllers\CompanyController::class, 'mySubscription']);
    // Subscription plans list (for upgrade form)
    Route::get('company/subscription/plans', [\App\Http\Controllers\CompanyController::class, 'subscriptionPlans']);
    // Latest upgrade request status
    Route::get('company/subscription/upgrade-request', [\App\Http\Controllers\CompanyController::class, 'myUpgradeRequest']);
    // Submit an upgrade request
    Route::post('company/subscription/request-upgrade', [\App\Http\Controllers\CompanyController::class, 'requestUpgrade']);
});

// Admin audit logs (company-scoped) - admin operational page, no subscription gate
Route::middleware(['auth:sanctum'])->prefix('admin/audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'adminIndex']);
    Route::get('/{id}', [AuditLogController::class, 'adminShow']);
    Route::patch('/{id}', [AuditLogController::class, 'adminUpdate']);
    Route::delete('/{id}', [AuditLogController::class, 'adminDestroy']);
    Route::delete('/', [AuditLogController::class, 'adminBulkDestroy']);
});

// Super User: Impersonate Business Admin
Route::get('superuser/businesses', [ImpersonateController::class, 'businesses']);
Route::post('superuser/impersonate/{businessId}', [ImpersonateController::class, 'impersonate']);

// Super User: Subscription & Billing
Route::get('superuser/subscriptions', [SubscriptionController::class, 'index']);
Route::post('superuser/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);
Route::post('superuser/subscriptions/{id}/activate', [SubscriptionController::class, 'activate']);
Route::post('superuser/subscriptions/{id}/deactivate', [SubscriptionController::class, 'deactivate']);

// Super User subscription routes (used by SubscriptionsPage.vue)
Route::middleware(['auth:sanctum'])->group(function () {
    // Subscriptions
    Route::get('super/subscriptions', [SuperSubscriptionController::class, 'index']);
    Route::patch('super/subscriptions/{id}/activate', [SuperSubscriptionController::class, 'activate']);
    Route::patch('super/subscriptions/{id}/deactivate', [SuperSubscriptionController::class, 'deactivate']);
    Route::post('super/subscriptions/{id}/renew', [SuperSubscriptionController::class, 'renew']);
    Route::post('super/subscriptions/{id}/trial', [SuperSubscriptionController::class, 'assignTrial']);
    Route::get('super/subscriptions/{id}/transactions', [SuperSubscriptionController::class, 'transactions']);
    Route::put('super/subscriptions/{id}/plan', [SuperSubscriptionController::class, 'changePlan']);
    Route::post('super/subscriptions/company/{companyId}', [SuperSubscriptionController::class, 'createForCompany']);
    Route::get('super/companies-without-subscription', [SuperSubscriptionController::class, 'companiesWithoutSubscription']);

    // Plans
    Route::get('super/plans', [SuperSubscriptionController::class, 'listPlans']);
    Route::post('super/plans', [SuperSubscriptionController::class, 'storePlan']);
    Route::put('super/plans/{id}', [SuperSubscriptionController::class, 'updatePlan']);
    Route::delete('super/plans/{id}', [SuperSubscriptionController::class, 'deletePlan']);

    // Upgrade Requests
    Route::get('super/upgrade-requests', [SuperSubscriptionController::class, 'listUpgradeRequests']);
    Route::post('super/upgrade-requests/{id}/approve', [SuperSubscriptionController::class, 'approveUpgrade']);
    Route::post('super/upgrade-requests/{id}/reject', [SuperSubscriptionController::class, 'rejectUpgrade']);
});

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
Route::post('business-categories/import-csv', [GlobalSettingsController::class, 'importCategoriesCsv']);

// Product Categories
Route::post('product-categories/bulk-upload', [ProductCategoryController::class, 'bulkUpload']);
Route::apiResource('product-categories', \App\Http\Controllers\ProductCategoryController::class);

// Warehouses
Route::apiResource('warehouses', \App\Http\Controllers\WarehouseController::class);

// Payment Methods
Route::get('payment-methods/enabled', [\App\Http\Controllers\PaymentMethodController::class, 'enabled']);
Route::post('payment-methods/{id}/toggle', [\App\Http\Controllers\PaymentMethodController::class, 'toggle']);
Route::apiResource('payment-methods', \App\Http\Controllers\PaymentMethodController::class);

// M-Pesa (subscription-gated)
Route::middleware(['auth:sanctum', 'feature:mpesa'])->group(function () {
    Route::post('mpesa/stk-push', [MpesaController::class, 'stkPush']);
    Route::post('mpesa/stk-query', [MpesaController::class, 'stkQuery']);
    Route::get('mpesa/transactions/{checkoutRequestId}', [MpesaController::class, 'show']);
});

Route::post('mpesa/callback', [MpesaController::class, 'callback']);

// UOMs (Units of Measure)
Route::get('uoms/conversion-factor', [\App\Http\Controllers\UOMController::class, 'conversionFactor']);
Route::apiResource('uoms', \App\Http\Controllers\UOMController::class);

// Roles and Companies for superuser interface
Route::get('roles', [RoleController::class, 'index']);
Route::get('companies', [CompanyController::class, 'getCompanies']);

// Messaging (SMS/Email) - with sms feature requirement
Route::middleware(['auth:sanctum', 'feature:sms'])->group(function () {
    Route::get('messaging/templates', [MessagingController::class, 'getTemplates']);
    Route::get('messaging/templates/{messageTemplate}', [MessagingController::class, 'getTemplate']);
    Route::post('messaging/templates', [MessagingController::class, 'createTemplate']);
    Route::put('messaging/templates/{messageTemplate}', [MessagingController::class, 'updateTemplate']);
    Route::delete('messaging/templates/{messageTemplate}', [MessagingController::class, 'deleteTemplate']);
    
    Route::post('messaging/send', [MessagingController::class, 'sendMessage']);
    Route::post('messaging/send-bulk', [MessagingController::class, 'sendBulkMessage']);
    Route::post('messaging/test-template', [MessagingController::class, 'testTemplate']);
    
    Route::get('messaging/logs', [MessagingController::class, 'getMessageLogs']);
    Route::get('messaging/stats', [MessagingController::class, 'getStats']);
    Route::post('messaging/initialize-defaults', [MessagingController::class, 'initializeDefaultTemplates']);
});


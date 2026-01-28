<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Company Settings (for Admin and SuperUser)
        if (!Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                
                // Business Information
                $table->string('business_hours_start')->nullable();
                $table->string('business_hours_end')->nullable();
                $table->string('timezone')->default('Africa/Nairobi');
                $table->string('currency')->default('KES');
                $table->string('currency_symbol')->default('KSh');
                $table->integer('decimal_places')->default(2);
                
                // Tax Settings
                $table->boolean('tax_enabled')->default(false);
                $table->string('tax_name')->nullable(); // VAT, GST, etc.
                $table->decimal('tax_rate', 5, 2)->default(0.00); // 16.00 for 16% VAT
                $table->boolean('tax_inclusive')->default(true); // Tax included in price
                
                // Receipt/Invoice Settings
                $table->string('receipt_header')->nullable();
                $table->text('receipt_footer')->nullable();
                $table->boolean('auto_print_receipt')->default(false);
                $table->string('receipt_logo_path')->nullable();
                $table->string('invoice_prefix')->default('INV-');
                $table->integer('invoice_number_start')->default(1000);
                
                // Inventory Settings
                $table->boolean('low_stock_alerts')->default(true);
                $table->integer('low_stock_threshold')->default(10);
                $table->boolean('allow_negative_stock')->default(false);
                $table->boolean('track_stock_expiry')->default(false);
                
                // Sales Settings
                $table->boolean('require_customer_info')->default(false);
                $table->boolean('allow_discount')->default(true);
                $table->decimal('max_discount_percent', 5, 2)->default(20.00);
                $table->boolean('allow_credit_sales')->default(false);
                
                // Notification Settings
                $table->boolean('email_notifications')->default(true);
                $table->boolean('sms_notifications')->default(false);
                $table->string('notification_email')->nullable();
                $table->string('notification_phone')->nullable();
                
                // Security Settings
                $table->boolean('require_receipt_approval')->default(false);
                $table->boolean('enable_audit_log')->default(true);
                $table->integer('session_timeout_minutes')->default(60);
                $table->boolean('two_factor_auth')->default(false);
                
                // Backup Settings
                $table->boolean('auto_backup')->default(false);
                $table->string('backup_frequency')->default('daily'); // daily, weekly, monthly
                $table->integer('backup_retention_days')->default(30);
                
                $table->timestamps();
                
                // Ensure one settings record per company
                $table->unique('company_id');
            });
        }

        // User Settings (for all roles - personal preferences)
        if (!Schema::hasTable('user_settings')) {
            Schema::create('user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                // Display Preferences
                $table->string('theme')->default('light'); // light, dark
                $table->string('language')->default('en');
                $table->integer('items_per_page')->default(20);
                $table->string('date_format')->default('Y-m-d');
                $table->string('time_format')->default('H:i');
                
                // Notification Preferences
                $table->boolean('email_notifications')->default(true);
                $table->boolean('push_notifications')->default(true);
                $table->boolean('low_stock_alerts')->default(true);
                $table->boolean('sale_alerts')->default(false);
                $table->boolean('report_alerts')->default(true);
                
                // Dashboard Preferences
                $table->json('dashboard_widgets')->nullable(); // Store visible widgets
                $table->string('default_page')->default('/'); // Landing page after login
                
                // Receipt Preferences
                $table->boolean('auto_print_receipt')->default(false);
                $table->string('printer_name')->nullable();
                
                $table->timestamps();
                
                // Ensure one settings record per user
                $table->unique('user_id');
            });
        }

        // System Settings (SuperUser only - global settings)
        // Check if table exists and if it has the new columns
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value')->nullable();
                $table->string('type')->default('string');
                $table->text('description')->nullable();
                $table->string('group')->default('general');
                $table->boolean('is_public')->default(false);
                $table->timestamps();
            });
        } else {
            // Update existing table structure if needed
            Schema::table('system_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('system_settings', 'type')) {
                    $table->string('type')->default('string')->after('value');
                }
                if (!Schema::hasColumn('system_settings', 'group')) {
                    $table->string('group')->default('general')->after('description');
                }
            });
        }

        // Email Templates (for all roles)
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('name'); // Template name
                $table->string('slug')->unique(); // welcome_email, receipt_email, etc.
                $table->string('subject');
                $table->text('body_html');
                $table->text('body_text')->nullable();
                $table->json('variables')->nullable(); // Available template variables
                $table->boolean('is_active')->default(true);
                $table->string('type')->default('transactional'); // transactional, marketing
                $table->timestamps();
            });
        }

        // Feature Toggles (SuperUser - enable/disable features per company)
        if (!Schema::hasTable('feature_toggles')) {
            Schema::create('feature_toggles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade'); // null = global
                $table->string('feature_key'); // expenses_module, multi_currency, etc.
                $table->boolean('is_enabled')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                
                $table->unique(['company_id', 'feature_key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_toggles');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('company_settings');
    }
};

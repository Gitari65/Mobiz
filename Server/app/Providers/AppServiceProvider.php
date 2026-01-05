<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\ModelActivityObserver;
use App\Models\User;
use App\Models\Company;
use App\Models\Product;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model activity observer for critical models
        User::observe(ModelActivityObserver::class);
        Company::observe(ModelActivityObserver::class);
        Product::observe(ModelActivityObserver::class);

        // Prevent running destructive DB artisan commands on production by mistake
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            $dangerCommands = [
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
                'db:wipe',
                'migrate:rollback'
            ];

            foreach ($dangerCommands as $cmd) {
                foreach ($argv as $arg) {
                    if (strpos($arg, $cmd) !== false && $this->app->environment('production')) {
                        // Write message and abort
                        fwrite(STDERR, "Aborting: attempted to run '{$cmd}' in production environment. Use local/test env or ensure you have verified backups.\n");
                        exit(1);
                    }
                }
            }
        }
    }
}

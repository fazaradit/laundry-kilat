<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // Pagination default Laravel memakai markup Tailwind; project ini hanya
        // memakai CSS polos, jadi seluruh ->links() diarahkan ke view custom.
        Paginator::defaultView('vendor.pagination.custom');
    }
}

<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\File;
use App\Observers\EmployeeObserver;
use App\Observers\PositionObserver;
use App\Observers\FileObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        
        // Register model observers for activity logging
        Employee::observe(EmployeeObserver::class);
        Position::observe(PositionObserver::class);
        File::observe(FileObserver::class);
    }
}

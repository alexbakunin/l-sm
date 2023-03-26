<?php

namespace App\Providers;

use App;
use DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Log;

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
        Paginator::useBootstrap();
        if (App::environment('local'))
        {
            DB::listen(function($query) {
                Log::stack(['sql'])->info(
                    $query->sql,
                    $query->bindings,
                    $query->time
                );
            });
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use App\Models\SocialMedia;
use App\Models\AdminPage;
use App\Helpers\PermissionHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
{
    $this->app->singleton(\App\Services\GasApiService::class);
}


    /**
     * Bootstrap any application services.
     */
 

    

    public function boot()
    {
        View::composer('landing-page.layouts.app', function ($view) {
            $view->with('socialMedias', SocialMedia::orderBy('platform')->get());
        });
        Carbon::setLocale('id');

        View::composer('landing-page.admin.layouts.app', function ($view) {
            $menuItems = AdminPage::active()
                ->get()
                ->filter(fn ($page) => PermissionHelper::can($page->slug));

            $view->with('menuItems', $menuItems);
        });
    }

    
}

<?php

namespace App\Providers;

use App\Models\File;

use App\Models\User;
use App\Models\Folder;
use App\Policies\FilePolicy;
use App\Policies\UserPolicy;
use App\Policies\FolderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        Gate::policies([
            File::class => FilePolicy::class,
            User::class => UserPolicy::class,
            Folder::class => FolderPolicy::class
        ]);

        Route::bind('trashFile', function ($id) {
            return File::onlyTrashed()->findOrFail($id);
        });
    }
}

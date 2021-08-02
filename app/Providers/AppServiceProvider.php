<?php

namespace App\Providers;

use App\Contracts\FileManager as ContractsFileManager;
use App\Library\FileManager\Features\ListDirectory;
use App\Library\FileManager\Features\MakeDirectory;
use App\Library\FileManager\Features\Remove;
use App\Library\FileManager\FileManager;
use App\Library\FileManager\Helper as FileManagerHelper;
use Illuminate\Support\Facades\Storage;
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
        $FileManagerStorage = Storage::disk(config('filemanager.disk'));

        $this->app->bind(FileManagerHelper::class, function () use ($FileManagerStorage) {
            return new FileManagerHelper($FileManagerStorage);
        });

        $this->app->bind(ListDirectory::class, function ($app) use ($FileManagerStorage) {
            return new ListDirectory($FileManagerStorage, $app->make(FileManagerHelper::class));
        });

        $this->app->bind(MakeDirectory::class, function ($app) use ($FileManagerStorage) {
            return new MakeDirectory($FileManagerStorage, $app->make(FileManagerHelper::class));
        });

        $this->app->bind(Remove::class, function ($app) use ($FileManagerStorage) {
            return new Remove($FileManagerStorage, $app->make(FileManagerHelper::class));
        });

        $this->app->bind(ContractsFileManager::class, function ($app) {
            new FileManager(
                $app->make(ListDirectory::class),
                $app->make(MakeDirectory::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Older shared-hosting MySQL / MariaDB versions cap index keys at
        // 767 or 1000 bytes. With utf8mb4 (4 bytes per char), VARCHAR(255)
        // overflows the limit. Capping default string length to 191 keeps
        // VARCHAR PKs at 764 bytes, which fits everywhere.
        Schema::defaultStringLength(191);
    }
}
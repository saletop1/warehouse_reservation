<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Set default timezone ke Asia/Jakarta
        date_default_timezone_set('Asia/Jakarta');
        config(['app.timezone' => 'Asia/Jakarta']);

        // Set Carbon locale dan timezone
        Carbon::setLocale('id');

    }

    public function register()
    {
        //
    }

    /**
     * Register custom Carbon macros
     */
    protected function registerCarbonMacros()
    {
        // Macro untuk format Indonesia
        Carbon::macro('toIndoFormat', function ($format = 'd/m/Y H:i:s') {
            return $this->format($format);
        });

        // Macro untuk format Indonesia dengan waktu
        Carbon::macro('toIndoDateTime', function () {
            return $this->format('d/m/Y H:i:s');
        });

        // Macro untuk format Indonesia tanggal saja
        Carbon::macro('toIndoDate', function () {
            return $this->format('d/m/Y');
        });

        // Macro untuk format Indonesia waktu saja
        Carbon::macro('toIndoTime', function () {
            return $this->format('H:i:s');
        });

        // Macro untuk format tampilan modal (d/m/Y, H.i.s)
        Carbon::macro('toModalFormat', function () {
            return $this->format('d/m/Y, H.i.s');
        });
    }
}

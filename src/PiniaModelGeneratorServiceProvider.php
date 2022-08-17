<?php

namespace Dev1437\PiniaModelGenerator;

use Dev1437\PiniaModelGenerator\Console\GeneratePiniaModels;
use Illuminate\Support\ServiceProvider;

class PiniaModelGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GeneratePiniaModels::class,
            ]);
        }
    }
}
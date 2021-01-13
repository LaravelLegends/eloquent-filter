<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Support\ServiceProvider;

class FilterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(\LaravelLegends\EloquentFilter\Filter::class, static function () {
            return new Filter;
        });
    }
}
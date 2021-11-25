<?php

namespace LaravelLegends\EloquentFilter\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\ModelFilter;

class FilterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Filter::class, static function () {
            return new Filter;
        });

        Builder::macro('withFilter', function (ModelFilter $modelFilter, $input = null) {
            $modelFilter->apply($this, $input);
            return $this;
        });
    }
}

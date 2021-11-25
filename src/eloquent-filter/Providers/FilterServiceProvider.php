<?php

namespace LaravelLegends\EloquentFilter\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use LaravelLegends\EloquentFilter\Console\FilterMakeCommand;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\ModelFilter;

class FilterServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(Filter::class, static function () {
            return new Filter;
        });

        $this->app->singleton(FilterMakeCommand::class, function ($app) {
            return new FilterMakeCommand($app['files']);
        });

        $this->commands(FilterMakeCommand::class);

        // Builder::macro('withFilter', function (ModelFilter $modelFilter, $input = null) {
        //     $modelFilter->apply($this, $input);
        //     return $this;
        // });
    }
}

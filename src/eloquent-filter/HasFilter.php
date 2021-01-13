<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Filter;
/**
 * This trait can be used in Eloquent models
 * 
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
trait HasFilter
{
    public function scopeFilter($query, Request $request = null)
    {
        $this->getEloquentFilter()->apply($query, $request ?: request());
        
        return $query;
    }

    public function getEloquentFilter()
    {
        return app(Filter::class);
    }
}
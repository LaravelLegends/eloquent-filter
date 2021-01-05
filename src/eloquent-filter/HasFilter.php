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
        Filter::make()->apply($query, $request ?: request());
        
        return $query;
    }
}
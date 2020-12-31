<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Filter;

trait HasFilter
{
    public function scopeFilter($query, Request $request = null)
    {
        Filter::make()->apply($query, $request ?: request());
        
        return $query;
    }
}
<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;

trait HasFilter
{
    public function scopeFilter($query, Request $request = null)
    {
        Filter::make()->apply($query, $request ?: request());
        
        return $query;
    }
}
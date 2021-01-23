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

        $filter = $this->getEloquentFilter();

        if (isset($this->filterRestrictions)) {
            $filter->restrict($this->filterRestrictions);
        }
        
        $filter->apply($query, $request ?: request())->unrestricted();
        
        return $query;
    }

    public function getEloquentFilter()
    {
        return app(Filter::class);
    }
}
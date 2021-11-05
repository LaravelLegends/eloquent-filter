<?php

namespace LaravelLegends\EloquentFilter;

use LaravelLegends\EloquentFilter\Filter;
/**
 * This trait can be used in Eloquent models
 * 
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
trait HasFilter
{
    /**
     * Scope for apply filters from Request
     * 
     * @param Builder $query
     * @param Illuminate\Http\Request|array $arrayOrRequest
     */
    public function scopeFilter($query, $arrayOrRequest = null)
    {
        $filter = $this->getEloquentFilter();

        $allowedFilters = $this->allowedFilters ?? $this->filterRestrictions ?? null;

        $allowedFilters && $filter->allow($allowedFilters);
        
        $filter->apply($query, $arrayOrRequest ?: app('request'))->allowAll();
        
        return $query;
    }

    public function getEloquentFilter()
    {
        return app(Filter::class);
    }
}
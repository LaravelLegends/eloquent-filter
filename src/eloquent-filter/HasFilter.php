<?php

namespace LaravelLegends\EloquentFilter;

use LaravelLegends\EloquentFilter\Contracts\Filterable;
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Illuminate\Http\Request|array $arrayOrRequest
     * 
     * @return Builder
     */
    public function scopeFilter($query, $arrayOrRequest = null)
    {
        $filter = $this->getEloquentFilter();

        if ($this instanceof Filterable) {
            $filter->allow($this->getFilterable());
        } elseif (property_exists($this, 'allowedFilters')) {
            $filter->allow($this->allowedFilters);
        }
        
        $filter->apply($query, $arrayOrRequest ?: app('request'))->allowAll();
        
        return $query;
    }

    /**
     * Gets the Eloquent Filter instance
     * 
     * @return \LaravelLegends\EloquentFilter\Filter
     */
    public function getEloquentFilter(): Filter
    {
        return app(Filter::class);
    }
}
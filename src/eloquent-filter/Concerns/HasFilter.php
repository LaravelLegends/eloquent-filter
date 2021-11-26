<?php

namespace LaravelLegends\EloquentFilter\Concerns;

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\Filters\ModelFilter;

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
     * @deprecated 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request|array $input
     * @return Builder
     */
    public function scopeFilter($query, $input = null)
    {
        $filter = $this->getEloquentFilter();

        if (method_exists($this, 'getFilterable')) {
            $filter->allow($this->getFilterable());
        } elseif (property_exists($this, 'filterable')) {
            $filter->allow($this->filterable);
        }
        
        $filter->apply($query, $input ?: app('request'))->allowAll();
        
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

    /**
     * Applies a filter to current query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param ModelFilter $filter
     * @param array|\Illuminate\Http\Request $input
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFilter(Builder $query, ModelFilter $filter, $input = null)
    {
        $filter->apply($query, $input);
    
        return $query;
    }
}
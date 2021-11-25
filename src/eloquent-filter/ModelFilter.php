<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Contracts\Filterable;

abstract class ModelFilter implements Filterable
{

    public function customRules(): array
    {
        return [];
    }

    public function getFilter(): Filter 
    {
        $filter = (new Filter)->allow($this->getFilterable());

        foreach ($this->customRules() as $name => $rule) {
            $filter->setRule($name, $rule);
        }

        return $filter;
    }

    public function apply(Builder $query, $input = null)
    {
        return $this->getFilter()->apply($query, $input ?? app('request'));
    }
}
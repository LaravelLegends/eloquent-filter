<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class In implements ApplicableFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->whereIn($field, $value);
    }
}
<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class EndsWith implements ApplicableFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, 'LIKE', "%{$value}");
    }
}
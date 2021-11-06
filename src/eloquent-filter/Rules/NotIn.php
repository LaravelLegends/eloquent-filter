<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class NotIn implements ApplicableFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->whereNotIn($field, $value);
    }
}
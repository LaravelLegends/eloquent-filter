<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class YearMax implements ApplicableFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->whereYear($field, '<=', $value);
    }
}

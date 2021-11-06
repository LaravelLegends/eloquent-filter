<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class DateMin implements ApplicableFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->whereDate($field, '>=', $value);
    }
}
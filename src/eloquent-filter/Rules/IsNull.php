<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;


class IsNull implements ApplicableFilter
{
    public function __invoke($query, $field, $boolean)
    {
       $boolean ? $query->whereNull($field) : $query->whereNotNull($field);
    }
}
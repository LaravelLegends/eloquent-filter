<?php

namespace LaravelLegends\EloquentFilter\Rules;

class Exact implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, '=', $value);
    }
}
<?php

namespace LaravelLegends\EloquentFilter\Rules;

class StartsWith implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, 'LIKE', "{$value}%");
    }
}
<?php

namespace LaravelLegends\EloquentFilter\Rules;

class Contains implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, 'LIKE', "%{$value}%");
    }
}
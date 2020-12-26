<?php

namespace LaravelLegends\EloquentFilter\Rules;

class Min implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, '>=', $value);
    }
}
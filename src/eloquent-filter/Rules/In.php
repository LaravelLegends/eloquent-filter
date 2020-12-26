<?php

namespace LaravelLegends\EloquentFilter\Rules;

class In implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->whereIn($field, $value);
    }
}
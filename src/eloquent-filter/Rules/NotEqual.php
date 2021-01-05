<?php

namespace LaravelLegends\EloquentFilter\Rules;

class NotEqual implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->where($field, '<>', $value);
    }
}

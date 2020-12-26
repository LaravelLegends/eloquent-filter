<?php

namespace LaravelLegends\EloquentFilter\Rules;

class NotIn implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->whereNotIn($field, $value);
    }
}
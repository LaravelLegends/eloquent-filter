<?php

namespace LaravelLegends\EloquentFilter\Rules;

class YearExact implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->whereYear($field, '=', $value);
    }
}

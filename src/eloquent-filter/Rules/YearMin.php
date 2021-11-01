<?php

namespace LaravelLegends\EloquentFilter\Rules;

class YearMin implements Searchable
{
    public function __invoke($query, $field, $value)
    {
        $query->whereYear($field, '=>', $value);
    }
}

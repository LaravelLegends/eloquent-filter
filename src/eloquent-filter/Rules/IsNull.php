<?php

namespace LaravelLegends\EloquentFilter\Rules;

class IsNull implements Searchable
{
    public function __invoke($query, $field, $boolean)
    {
       $boolean ? $query->whereNull($field) : $query->whereNotNull($field);
    }
}
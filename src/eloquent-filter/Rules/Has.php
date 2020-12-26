<?php

namespace LaravelLegends\EloquentFilter\Rules;

class Has implements Searchable
{
    public function __invoke($query, $field, $boolean)
    {
       $boolean ? $query->has($field) : $query->doesntHave($field);
    }
}
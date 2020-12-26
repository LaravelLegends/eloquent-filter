<?php

namespace LaravelLegends\EloquentFilter\Rules;

interface Searchable
{
    public function __invoke($query, $field, $value);
}
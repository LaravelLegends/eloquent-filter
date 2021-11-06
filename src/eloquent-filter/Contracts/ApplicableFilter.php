<?php

namespace LaravelLegends\EloquentFilter\Contracts;

interface ApplicableFilter
{
    public function __invoke($query, $field, $value);
}

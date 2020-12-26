<?php

namespace LaravelLegends\EloquentFilter\Rules\Traits;

trait CheckEmptyArray
{
    public function isEmpty($value)
    {
        return $value === [];
    }
}
<?php

namespace LaravelLegends\EloquentFilter\Rules;

use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;
use LaravelLegends\EloquentFilter\Contracts\RelationFilter;

class NotIn implements ApplicableFilter, RelationFilter
{
    public function __invoke($query, $field, $value)
    {
        $query->whereNotIn($field, $value);
    }

    public function forRelation($query, string $relation, string $field, $value)
    {
        $query->whereDoesntHave($relation, static function ($query) use ($field, $value) {
            $query->whereIn($field, $value);
        });
    }
}
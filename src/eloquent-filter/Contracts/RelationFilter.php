<?php

namespace LaravelLegends\EloquentFilter\Contracts;

interface RelationFilter
{
    public function forRelation($query, string $relation, string $field, $value);
}

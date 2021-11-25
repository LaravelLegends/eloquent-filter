<?php

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\ModelFilter;
use LaravelLegends\EloquentFilter\Rules\Exact;

class UserFilter extends ModelFilter
{

    public function customRules(): array
    {
        return [
            'icontains' => function (Builder $query, string $field, $value) {
                $query->where($field, 'ilike', "%{$value}%");
            },
            'eq' => Exact::class,
        ];
    }

    public function getFilterable(): array
    {
        return [
            'id'   => 'eq',
            'age'  => ['max', 'min'],
            'name' => ['contains', 'icontains'],
        ];
    }
}
<?php

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\ModelFilter;
use LaravelLegends\EloquentFilter\Rules\Exact;

class UserPhoneFilter extends ModelFilter
{
    public function getFilterable(): array
    {
        return [
            'code'   => 'exact',
            'number' => 'exact',
        ];
    }
}
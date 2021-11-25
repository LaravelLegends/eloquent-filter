<?php

namespace Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Filters\ModelFilter;
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
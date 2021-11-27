<?php

namespace Filters;

use LaravelLegends\EloquentFilter\Filters\ModelFilter;

class RoleFilter extends ModelFilter
{

    public function getFilterables(): array
    {
        return [
            'id'    => 'exact',
            'name'  => ['contains', 'starts_with', 'ends_with'],
            'users' => new UserFilter,
        ];
    }
}
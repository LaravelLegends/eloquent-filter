<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Contracts\Filterable;
use LaravelLegends\EloquentFilter\HasFilter;

class Role extends Model implements Filterable
{
    use HasFilter;

    public function getFilterable(): array
    {
        return [
            'name' => '*',
            'id'   => 'exact'
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }
}
<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Concerns\HasFilter;
class Role extends Model
{
    use HasFilter;

    public function getFilterables(): array
    {
        return [
            'name' => '*',
            'id'   => ['exact', 'not_equal']
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }
}
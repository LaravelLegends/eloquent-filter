<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Concerns\HasFilter;
class Role extends Model
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
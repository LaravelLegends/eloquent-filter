<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Concerns\HasFilter;
use LaravelLegends\EloquentFilter\Contracts\Filterable;
class User extends Model implements Filterable
{
    use HasFilter;
    

    protected $table = 'users';

    public $allowedFilters = [
        'name'           => 'contains',
        'email'          => true,
        'age'            => ['max', 'min'],
        'roles.id'       => ['exact', 'not_equal'],
        'roles.name'     => ['in', 'not_in'],
        'roles.disabled' => '*'
        
    ];

    public function getFilterable(): array
    {
        return $this->allowedFilters;
    }

    public function phones()
    {
        return $this->hasMany(UserPhone::class);
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

}
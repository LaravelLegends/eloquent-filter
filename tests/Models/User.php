<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Concerns\HasFilter;
class User extends Model
{
    use HasFilter;

    protected $table = 'users';

    protected $filterables = [
        'name'           => 'contains',
        'email'          => true,
        'age'            => ['max', 'min'],
        'roles.id'       => ['exact', 'not_equal'],
        'roles.name'     => ['in', 'not_in'],
        'roles.disabled' => '*'  
    ];

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
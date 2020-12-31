<?php

use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\HasFilter;
class User extends Model
{
    use HasFilter;

    protected $table = 'users';

    public function phones()
    {
        return $this->hasMany(UserPhone::class);
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }
}
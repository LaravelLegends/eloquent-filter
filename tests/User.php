<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
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
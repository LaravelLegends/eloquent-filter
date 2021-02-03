<?php

use Illuminate\Database\Eloquent\Model;

class UserPhone extends Model
{
    protected $table = 'user_phones';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
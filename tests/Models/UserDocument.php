<?php

namespace Models;


use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    protected $table = 'user_documents';

    public function type()
    {
        return $this->belongsTo(UserDocumentType::class);
    }
}
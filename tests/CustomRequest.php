<?php

use Illuminate\Foundation\Http\FormRequest;

class CustomRequest extends FormRequest
{
    public function rules()
    {
        return [
            'contains.name' => 'string',
            'min.price'     => 'numeric',
            'x'             => 'nullable',
        ];
    }
}
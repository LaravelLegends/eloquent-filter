<?php

use LaravelLegends\EloquentFilter\ModelFilter;

class CustomFilter extends ModelFilter
{
    protected $filterable = [];

    protected $customRules = [];

    public function __construct(array $filterable, array $customRules = [])
    {
        $this->filterable = $filterable;    
        $this->customRules = $customRules;
    }

    public function getFilterable(): array
    {
        return $this->filterable;
    }

    public function customRules(): array
    {
        return $this->customRules;
    }
}
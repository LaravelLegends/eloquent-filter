<?php

namespace LaravelLegends\EloquentFilter\Filters;

/**
 * Base Filter class
 * 
 * @author Wallace Vizerra <wallacemaxters@gmail.com>
 */
class BaseFilter extends ModelFilter
{

    /**
     * Filterable values
     *
     * @var array
     */
    protected $filterable = [];

    /**
     * Custom rules
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * Constructor
     *
     * @param array $filterable
     * @param array $customRules
     */
    public function __construct(array $filterable, array $customRules = [])
    {
        $this->filterable = $filterable;    
        $this->customRules = $customRules;
    }

    /**
     * Filterable values
     *
     * @return array
     */
    public function getFilterable(): array
    {
        return $this->filterable;
    }

    /**
     * Custom rules
     *
     * @return array
     */
    public function customRules(): array
    {
        return $this->customRules;
    }
}
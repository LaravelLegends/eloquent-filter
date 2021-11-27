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
    protected $filterables = [];

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
    public function __construct(array $filterables, array $customRules = [])
    {
        $this->filterables = $filterables;    
        $this->customRules = $customRules;
    }

    /**
     * Filterable values
     *
     * @return array
     */
    public function getFilterables(): array
    {
        return $this->filterables;
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
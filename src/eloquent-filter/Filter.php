<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Rules;
use LaravelLegends\EloquentFilter\Rules\Searchable;

class Filter
{

    protected $rules = [
        'max'         => Rules\Max::class,
        'min'         => Rules\Min::class,
        'contains'    => Rules\Contains::class,
        'ends_with'   => Rules\EndsWith::class,
        'starts_with' => Rules\StartsWith::class,
        'exact'       => Rules\Exact::class,
        'has'         => Rules\Has::class,
        'is_null'     => Rules\IsNull::class,
        'in'          => Rules\In::class,
        'not_in'      => Rules\NotIn::class,
    ];

    /**
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     */
    public function apply(Builder $query, Request $request)
    {
        $query->where($this->getCallback($request));

        return $this;
    }

    /**
     * Get the callback with queries created from request to filter the models
     * 
     * @param Request $request
     * @return \Closure
     */
    public function getCallback(Request $request)
    {
        $rules = $this->getRulesFromRequest($request);
        
        return function ($query) use($rules) {
            
            foreach ($rules as $rule_name => $fields) {
                $fields && $this->applyRule($query, $rule_name, $fields);
            }
            
            return $query;
        };
    }

    /**
     * Extracts the parameters used in model filters from request
     * 
     * @return array
     */
    protected function getRulesFromRequest(Request $request)
    {
        $params = array_keys($this->rules);

        return $request->only($params);
    }

    /**
     * Apply filter rule in query
     * 
     * @param $query
     * @param string $name
     * @param array $fields
     * 
     * @return static
     */
    public function applyRule($query, $name, array $fields)
    {
        $rule = $this->getRuleAsCallable($name);

        foreach ($fields as $field => $value) {
            $this->isEmpty($value) || $rule($query, $field, $value);
        }

        return $this;
    }

    public function getRule($name)
    {
        return $this->rules[$name];
    }

    public function setRule($name, $rule) 
    {
        if ($rule instanceof Searchable || is_callable($rule)) {
            $this->rules[$name] = $rule;
        }

        throw new \UnexpectedValueException('The rule should be callable or instance of ' . Searchable::class);
    }

    /**
     * Get the rule as callable
     * 
     * @param string $name
     */
    public function getRuleAsCallable($name)
    {
        $rule = $this->getRule($name);

        return is_callable($rule) ? $rule : new $rule;
    }

    protected function isEmpty($value)
    {
        return $value === '' || $value === [];
    }

    
    public static function fromModel($model, Request $request)
    {
        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            throw new \InvalidArgumentException('Only models can be passed by parameters');
        }

        $query = $model::query();

        (new static())->apply($query, $request);

        return $query;
    }


    public static function make()
    {
        return new static;
    }
}
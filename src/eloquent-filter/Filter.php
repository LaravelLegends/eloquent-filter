<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Rules;
use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Rules\Searchable;

/**
 * This class creates query filters based on request
 * 
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
class Filter
{

    protected $relation_separator = '.';

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
        'date_max'    => Rules\DateMax::class,
        'date_min'    => Rules\DateMin::class,
        'not_equal'   => Rules\NotEqual::class,
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

    public function applyWithoutNested(Builder $query, Request $request)
    {
        $this->getCallback($request)->__invoke($query);
        
        return $query;
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

            list($base_rules, $related) = $this->getGroupedRules($rules);

            foreach ($base_rules as $rule => $fields) {
                $this->applyRule($query, $rule, $fields);
            }

            foreach ($related as $relation => $rules) {
                $query->whereHas($relation, function ($subquery) use($rules) {
                    foreach ($rules as $rule => $fields) {
                        $this->applyRule($subquery, $rule, $fields);
                    }
                });
            }
            
            return $query;
        };
    }

    /**
     * Extracts the parameters used in model filters from request
     * 
     * @return array
     */
    public function getRulesFromRequest(Request $request)
    {

        if ($request instanceof \Illuminate\Foundation\Http\FormRequest) {

            $rules = [];

            foreach ($request->only(array_keys($request->rules())) as $key => $rule) {

                if (! $this->hasRule($key)) continue;
                
                $rules[$key] = $rule;
            }

            return $rules;
        }

        return $request->only(array_keys($this->rules));
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

            if ($this->isEmpty($value)) continue;

            $rule($query, $field, $value);
        }

        return $this;
    }

    /**
     * Gets the rule by name
     * 
     * @param string $name
     * 
     * @return string|Closure
     */
    public function getRule($name)
    {
        return $this->rules[$name];
    }


    /**
     * Check if contains rule by name
     * @return boolean
     */

    public function hasRule($name)
    {
        return isset($this->rules[$name]);
    }

    /**
     * Sets the rule
     * 
     * @param string $name
     * @param callable|\LaravelLegends\EloquentFilter\Rules\Searchable $rule
     * @throws \UnexpectedValueException on value is not callable or not implements Searchable interface
     */
    public function setRule($name, $rule) 
    {
        if ($rule instanceof Searchable || is_callable($rule)) {
            
            $this->rules[$name] = $rule;

            return $this;
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

    /**
     * Detect if value of request is "empty"
     * 
     * @return boolean
     */
    protected function isEmpty($value)
    {
        return $value === '' || $value === [];
    }

    /**
     * Get parsed data if field contains a expession than represents a relationship
     * @param string $field
     * @return array
     * 
     */ 
    protected function parseRelation($field)
    {
        $parts = explode($this->relation_separator, $field);

        return [array_pop($parts), implode($this->relation_separator, $parts)];
    }

    /**
     * Check if field expression contains a relationship
     * @return boolean
     */
    protected function containsRelation($field)
    {
        $index = strpos($field, $this->relation_separator);

        return $index > 0;
    }
    
    /**
     * Gets the separated group of rules with normal fields and related fields
     * @param array $rules
     * 
     * @return array
     */
    protected function getGroupedRules(array $rules)
    {
        $base = $related = [];

        foreach ($rules as $name => $fields) {

            if (! $fields) continue;

            list($base[$name], $related_fields) = $this->getGroupedFields($fields);

            foreach ($related_fields as $relation => $value) {
                $related[$relation][$name] = $value;
            }
        }

        return [$base, $related];
    }
    
    /**
     * Get grouped field by relations and base
     * 
     * @param array $fields
     * @return array
     */
    protected function getGroupedFields(array $fields)
    {
        $related = $base = [];

        foreach ($fields as $field => $value) {

            if ($this->containsRelation($field)) {

                list($field, $relation) = $this->parseRelation($field);
                
                $related[$relation][$field] = $value;

                continue;
            }

            $base[$field] = $value;
        }

        return [$base, $related];
    }


    /**
     * Apply filter directly in model
     * 
     * @param string Model class
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function fromModel($model, Request $request)
    {
        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            throw new \InvalidArgumentException('Only models can be passed by parameter');
        }

        static::make()->apply($query = $model::query(), $request);

        return $query;
    }


    public static function make()
    {
        return new static();
    }
}
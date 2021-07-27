<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Rules;
use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Rules\Searchable;
use LaravelLegends\EloquentFilter\Exceptions\RestrictionException;

/**
 * This class creates query filters based on request
 *
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
class Filter
{

    /**
     * @var array
     */
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
     * @var string
     */
    protected $relationSeparator = '.';

    /**
     * @var array
     */
    protected $allowedFilters = [];


    /**
     * @var \Closure|null
     */
    protected $dataCallback = null;

    /**
     * Apply the filter based on request
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
     * Apply the filter based on request without nested where
     *
     */
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
    public function getCallback(Request $request): \Closure
    {
        $preparedData = $this->getPreparedDataFromRequest($request);

        [$baseFilters, $relatedFilters] = $this->getGroupedFiltersByRules($preparedData);

        return function ($query) use ($baseFilters, $relatedFilters) {
            foreach ($baseFilters as $rule => $fields) {
                $this->applyRule($query, $rule, $fields);
            }

            foreach ($relatedFilters as $relation => $rules) {
                $query->whereHas($relation, function ($subquery) use ($rules) {
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
     * @deprecated
     * @return array
     */
    public function getRulesFromRequest(Request $request)
    {
        return $this->getPreparedDataFromRequest($request);   
    }

    /**
     * Gets the data from the request to be used in Filters
     * @param Request $request
     * @return array
     */
    public function getPreparedDataFromRequest(Request $request): array
    {
        $requestData = $this->prepareRequestData($request);

        $this->checkAllowedFields($requestData);
        
        return $requestData;

    }

    /**
     * Prepares the request data use in filters
     * 
     * @param Request $request
     * @return array
     */
    protected function prepareRequestData(Request $request): array
    {
        $rule_keys = array_keys($this->rules);
        
        if (null === $this->dataCallback) {
            return array_filter($request->only($rule_keys));
        }

        $rules = [];
        
        foreach ($rule_keys as $rule_key) {

            foreach ($request->all() as $key => $value) {

                [$key, $value] = ($this->dataCallback)($rule_key, $key, $value);

                $key && $rules[$rule_key][$key] = $value;
            }
        }

        return $rules;
    }

    public function setDataCallback(callable $callback)
    {
        $this->dataCallback = $callback;

        return $this;
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

            if ($this->isEmpty($value)) {
                continue;
            }

            $rule($query, $field, $value);
        }

        return $this;
    }

    /**
     * Gets the rule by name
     *
     * @param string $name
     * @return string|Closure
     */
    public function getRule($name)
    {
        return $this->rules[$name];
    }


    /**
     * Check if contains rule by name
     *
     * @param string $name
     * @return boolean
     */

    public function hasRule($name): bool
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
     *
     * @return callable
     */
    public function getRuleAsCallable($name): callable
    {
        $rule = $this->getRule($name);

        return is_callable($rule) ? $rule : new $rule;
    }

    /**
     * Detect if value of request is "empty"
     *
     * @return boolean
     */
    protected function isEmpty($value): bool
    {
        return $value === '' || $value === [];
    }

    /**
     * Get parsed data if field contains a expession than represents a relationship
     *
     * @param string $field
     * @return array
     */
    protected function parseRelation($field): array
    {
        $parts = explode($this->relationSeparator, $field);

        return [array_pop($parts), implode($this->relationSeparator, $parts)];
    }

    /**
     * Check if field expression contains a relationship
     *
     * @return boolean
     */
    protected function containsRelation($field): bool
    {
        $index = strpos($field, $this->relationSeparator);

        return $index > 0;
    }
    
    /**
     * Gets the separated group of rules with normal fields and related fields
     *
     * @param array $rules
     * @return array
     */
    protected function getGroupedFiltersByRules(array $rules): array
    {
        $commonFields = $relatedFields = [];

        foreach ($rules as $name => $fields) {
            if (! $fields) {
                continue;
            }

            [$commonFields[$name], $related] = $this->getGroupedFields($fields);

            foreach ($related as $relation => $value) {
                $relatedFields[$relation][$name] = $value;
            }
        }

        return [$commonFields, $relatedFields];
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
                [$field, $relation] = $this->parseRelation($field);
                
                $related[$relation][$field] = $value;

                continue;
            }

            $base[$field] = $value;
        }

        return [$base, $related];
    }


    /**
     * Define a list of allowed field and rules
     * 
     * @deprecated use "allow" instead of
     * @param array $restriction
     * @return self
     */
    public function restrict(array $allowedFields)
    {
        return $this->allow($allowedFields);
    }

    /**
     * Remove allowedFields
     * 
     * @deprecated use "allowAll" instead of
     * @return self
     */
    public function unrestricted()
    {
        return $this->allowAll();
    }

    /**
     * Check if filter contains allowedFields
     *
     * @param array $filterData
     * @throws \LaravelLegends\EloquentFilter\Exceptions\RestrictionException
     * @return void
    */
    protected function checkAllowedFields(array $filterData): void
    {
        if (empty($this->allowedFields)) {
            return;
        }

        foreach ($filterData as $rule => $fields) {
            foreach (array_keys($fields) as $field) {
                $this->checkAllowedFieldByRule($field, $rule);
            }
        }
    }

    /**
     *
     * @param string $field
     * @param string $rule
     * @throws \LaravelLegends\EloquentFilter\Exceptions\RestrictionException
     * @return void
     */
    protected function checkAllowedFieldByRule(string $field, string $rule)
    {
        if (!isset($this->allowedFields[$field])) {
            throw new RestrictionException(sprintf('Cannot use filter with "%s" field', $field));
        } elseif (in_array($this->allowedFields[$field], ['*', true], true) || in_array($rule, (array) $this->allowedFields[$field])) {
            return;
        }

        throw new RestrictionException(sprintf('Cannot use filter "%s" field with rule "%s"', $field, $rule));
    }

    /**
     * Set rules (values) allowed by fields (keys)
     * 
     * @param array $allowedFields
     * @return self
     */
    public function allow(array $allowedFilters)
    {
        $this->allowedFilters = $allowedFilters;

        return $this;
    }

    /**
     * Remove filter restrictions
     * 
     */
    public function allowAll()
    {
        $this->allowedFilters = [];

        return $this;
    }

    /**
     * Apply filter directly in model
     *
     * @param string Model class
     * @param \Illuminate\Http\Request $request
     * @param array|null $allowedFilters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function fromModel($model, Request $request, array $allowedFilters = [])
    {
        if (! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            throw new \InvalidArgumentException('Only models can be passed by parameter');
        }

        $filter = new static;

        $allowedFilters && $filter->allow($allowedFilters);
        
        $filter->apply($query = $model::query(), $request);

        return $query;
    }
}

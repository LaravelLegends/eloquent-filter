<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Rules;
use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Exceptions\RestrictionException;
use LaravelLegends\EloquentFilter\Contracts\ApplicableFilter;
use LaravelLegends\EloquentFilter\Contracts\RelationFilter;

/**
 * This class create easy filters for Eloquent model basead defined rules using an Array or Request data 
 *
 * @author Wallace Maxters <wallacemaxters@gmail.com>
 */
class Filter
{
    const RELATION_SEPARATOR = '.';

    /**
     * @var array
     */
    protected $rules = [
        'contains'    => Rules\Contains::class,
        'date_exact'  => Rules\DateExact::class,
        'date_max'    => Rules\DateMax::class,
        'date_min'    => Rules\DateMin::class,
        'ends_with'   => Rules\EndsWith::class,
        'exact'       => Rules\Exact::class,
        'has'         => Rules\Has::class,
        'in'          => Rules\In::class,
        'is_null'     => Rules\IsNull::class,
        'max'         => Rules\Max::class,
        'min'         => Rules\Min::class,
        'not_equal'   => Rules\NotEqual::class,
        'not_in'      => Rules\NotIn::class,
        'starts_with' => Rules\StartsWith::class,
        'year_exact'  => Rules\YearExact::class,
        'year_max'    => Rules\YearMax::class,
        'year_min'    => Rules\YearMin::class,
    ];

    /**
     * @var array
     */
    protected $filterables = [];

    /**
     * @var \Closure|null
     */
    protected $dataCallback = null;

    /**
     * Apply the filter based on request
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request|array $input
     * @return self
     */
    public function apply(Builder $query, $input): self
    {
        $callback = $this->getCallback($input);

        $query->where($callback);

        return $this;
    }

    /**
     * Apply the filter based on request without nested where
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function applyWithoutNested(Builder $query, Request $request): Builder
    {
        $this->getCallback($request)($query);

        return $query;
    }

    /**
     * Apply the filter basead on Array Data
     *
     * @param Builder $query
     * @param array   $data
     * @return Builder
     */
    public function applyFromArray(Builder $query, array $data): Builder
    {
        $query->where(
            $this->getCallbackFromArray($data)
        );

        return $query;
    }

    /**
     * Apply the filter from Request instance
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return mixed
     */
    public function applyFromRequest(Builder $query, Request $request): Builder
    {
        $query->where($this->getCallbackFromRequest($request));

        return $query;
    }

    /**
     * Get the callback with queries created from request to filter the models
     *
     * @param Request $request
     * @return \Closure
     */
    public function getCallbackFromRequest(Request $request): \Closure
    {
        $preparedData = $this->getPreparedDataFromRequest($request);

        return $this->getCallbackFromArray($preparedData);
    }

    /**
     * Creates the callback with queries based on array structure
     *
     * @param array $data
     * @return \Closure
     */
    public function getCallbackFromArray(array $data): \Closure
    {

        $this->checkAllowedFields($data);

        [$baseFilters, $relatedFilters] = $this->getGroupedFiltersByRules($data);

        return function ($query) use ($baseFilters, $relatedFilters) {
            foreach ($baseFilters as $rule => $fields) {
                $this->applyRule($query, $rule, $fields);
            }

            foreach ($relatedFilters as $relation => $rules) {
                foreach ($rules as $rule => $fields) {
                    $this->applyRuleToRelated($query, $rule, $relation, $fields);
                }
            }

            return $query;
        };
    }

    /**
     * Get the callback with queries created from request to filter the models
     *
     * @param Request|array $input
     * @return \Closure
     */
    public function getCallback($input): \Closure
    {
        if ($input instanceof Request) {
            return $this->getCallbackFromRequest($input);
        } elseif (is_array($input)) {
            return $this->getCallbackFromArray($input);
        }

        throw new \InvalidArgumentException(
            'The $input argument should be a Request or array'
        );
    }

    /**
     * Gets the data from the request to be used in Filters
     *
     * @param Request $request
     * @return array
     */
    public function getPreparedDataFromRequest(Request $request): array
    {
        return $this->prepareRequestData($request);
    }

    /**
     * Prepares the request data use in filters
     *
     * @param Request $request
     * @return array
     */
    public function prepareRequestData(Request $request): array
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

    
    /**
     * Define callback for passed data in array or request
     *
     * @param callable $callback
     * @return self
     */
    public function setDataCallback(callable $callback): self
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
     * @return self
     */
    public function applyRule($query, string $name, array $fields): self
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
     *  Applies rules to related field
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @param string $relation
     * @param array $fields
     * @return self
     */
    protected function applyRuleToRelated(Builder $query, string $name, string $relation, array $fields): self
    {
        $rule = $this->getRuleAsCallable($name);

        if ($rule instanceof RelationFilter) {
            foreach ($fields as $field => $value) {
                $rule->forRelation($query, $relation, $field, $value);
            }

            return $this;
        }

        $query->whereHas($relation, function ($query) use ($name, $fields) {
            $this->applyRule($query, $name, $fields);
        });

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
     * @param callable|string $rule
     * @throws \UnexpectedValueException on value is not callable or not implements ApplicableFilter interface
     */
    public function setRule(string $name, $rule)
    {
        if (is_subclass_of($rule, ApplicableFilter::class) || is_callable($rule)) {
            $this->rules[$name] = $rule;

            return $this;
        }

        throw new \UnexpectedValueException('The rule should be callable or instance of ' . ApplicableFilter::class);
    }

    /**
     * Get the rule as callable
     *
     * @param string $name
     *
     * @return callable
     */
    public function getRuleAsCallable(string $name): callable
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
    public static function parseRelation(string $field): array
    {
        $parts = explode(static::RELATION_SEPARATOR, $field);

        return [array_pop($parts), implode(static::RELATION_SEPARATOR, $parts)];
    }

    /**
     * Check if field expression contains a relationship
     *
     * @return boolean
     */
    public static function containsRelation(string $field): bool
    {
        $index = strpos($field, static::RELATION_SEPARATOR);

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
    protected function getGroupedFields(array $fields): array
    {
        $related = $base = [];

        foreach ($fields as $field => $value) {
            if (static::containsRelation($field)) {
                [$field, $relation] = static::parseRelation($field);

                $related[$relation][$field] = $value;

                continue;
            }

            $base[$field] = $value;
        }

        return [$base, $related];
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
        if (empty($this->filterables)) {
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
        if (!isset($this->filterables[$field])) {
            throw new RestrictionException(sprintf('Cannot use filter with "%s" field', $field));
        } elseif (in_array($this->filterables[$field], ['*', true], true) || in_array($rule, (array) $this->filterables[$field])) {
            return;
        }

        throw new RestrictionException(sprintf('Cannot use filter "%s" field with rule "%s"', $field, $rule));
    }

    /**
     * Set filterable fields (keys) acording to rules (values)
     *
     * @param array $filterable
     * @return self
     */
    public function setFilterables(array $filterables): self
    {
        $this->filterables = $filterables;

        return $this;
    }


    /**
     * Clear all filterable definitions
     *
     * @return self
     */
    public function clearFilterables(): self
    {
        return $this->setFilterables([]);
    }
}

<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class Filter
{
    protected $params = [
        'max',
        'min',
        'contains',
        'ends_with',
        'starts_with',
        'exact',
        'has',
        'is_null',
        'in',
        'not_in'
    ];

    protected $prefix = null;

    /**
     * @param string $prefix
     * 
     * 
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

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

    public function getCallback(Request $request)
    {
        $filters = $this->getFiltersByParams($request);
        
        return function ($query) use($filters) {
            
            foreach ($filters as $name => $fields) {

                $method = $this->getMethodFromFilterName($name);
                
                $this->$method((array) $fields, $query);
            }

            return $query;
        };
    }

    protected function getFiltersByParams(Request $request)
    {
        $params = $this->params;

        if ($this->prefix !== null) {

            $params = array_map(function ($param) {
                return $this->prefix . $param;
            }, $this->params);
        }

        return $request->only($params);
    }


    protected function applyByOperator(array $fields, $query, $operator) 
    {
        foreach ($fields as $name => $value) {            
            $this->isEmpty($value) || $query->where($name, $operator, $value);
        }

        return $this;
    }

    protected function applyByLikeOperator(array $fields, Builder $query, $str_template) 
    {
        foreach ($fields as $name => $value) {
            $this->isEmpty($value) || $query->where($name, 'LIKE', sprintf($str_template, $value));
        }

        return $this;
    }

    protected function applyMin(array $fields, $query)
    {
        $this->applyByOperator($fields, $query, '>=');
    }

    protected function applyMax(array $fields, $query)
    {
        $this->applyByOperator($fields, $query, '<=');
    }

    protected function applyExact(array $fields, $query)
    {
        $this->applyByOperator($fields, $query, '=');
    }

    protected function applyEndsWith(array $fields, $query)
    {
        $this->applyByLikeOperator($fields, $query, '%%%s');
    }

    protected function applyStartsWith(array $fields, $query)
    {
        $this->applyByLikeOperator($fields, $query, '%s%%');
    }

    protected function applyContains(array $fields, $query)
    {
        $this->applyByLikeOperator($fields, $query, '%%%s%%');
    }

    protected function applyHas(array $fields, $query)
    {
        foreach ($fields as $name => $field) {

            if ($this->isEmpty($field)) continue;

            $boolean = filter_var($field, FILTER_VALIDATE_BOOLEAN);

            $boolean ? $query->has($name) : $query->doesntHave($name);
        }
    }

    protected function applyIsNull(array $fields, $query)
    {
        foreach ($fields as $name => $field) {            

            if ($this->isEmpty($field)) continue;

            $boolean = filter_var($field, FILTER_VALIDATE_BOOLEAN);

            $boolean ? $query->whereNull($name) : $query->whereNotNull($name);
        }

        return $this;
    }


    protected function applyIn(array $fields, $query)
    {
        foreach ($fields as $name => $value) {
            $this->isEmpty($value) || $query->whereIn($name, $value);
        }
    }

    protected function applyNotIn(array $fields, $query)
    {
        foreach ($fields as $name => $value) {
            $this->isEmpty($value) || $query->whereNotIn($name, $value);
        }
    }


    protected function getMethodFromFilterName($name)
    {
        return 'apply' . implode('', array_map('ucfirst', explode('_', $name)));
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
}
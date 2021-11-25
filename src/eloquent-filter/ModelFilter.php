<?php

namespace LaravelLegends\EloquentFilter;

use Illuminate\Database\Eloquent\Builder;
use LaravelLegends\EloquentFilter\Contracts\Filterable;

/**
 * This a abstract class that should be extended to represent a model filter
 * 
 * @author Wallace Vizerra <wallacemaxters@gmail.com>
 */

abstract class ModelFilter implements Filterable
{

    /**
     * Define costum rules for current Filterable. The array should be return a ApplicableFilter class or Closure
     *
     * @return array
     */
    public function customRules(): array
    {
        return [];
    }

    /**
     * Gets the all filterabled data including Filterable instances as prefixed key
     * 
     * @return array
     */
    public function getFilterableWithParsedRelations(): array
    {
        $result = [];

        foreach ($this->getFilterable() as $field => $rule) {

            if ($rule instanceof Filterable) {

                $result += static::toRelatedFilterable($rule, $field, false);

                continue;
            }

            $result[$field] = $rule;

        }

        return $result;
    }

    /**
     * Prefix all keys of a Filterable. Is useful to parse Filterable on return of getFilterable 
     *
     * @param Filterable $filter
     * @param string $prefix
     * @param boolean $allowChildFilters
     * @return array
     */
    public static function toRelatedFilterable(Filterable $filter, string $prefix, bool $allowChildFilters = false): array
    {
        $data = $filter->getFilterable();

        if ($allowChildFilters === false) {
            $data = array_filter($data, function ($item) {
                return !$item instanceof self;
            });
        }

        $keys = array_map(static function (string $key) use($prefix) {
            return $prefix . Filter::RELATION_SEPARATOR . $key;
        }, array_keys($data));

        return array_combine($keys, $data);
    }

    /**
     * Constructs the base filter based on Filterable 
     *
     * @return Filter
     */
    public function getBaseFilter(): Filter 
    {
        $filter = (new Filter)->allow($this->getFilterableWithParsedRelations());

        foreach ($this->customRules() as $name => $rule) {
            $filter->setRule($name, $rule);
        }

        return $filter;
    }

    /**
     * Applies filter on Eloquent Query
     *
     * @param Builder $query
     * @param array|Request $input
     * @return void
     */
    public function apply(Builder $query, $input = null)
    {
        return $this->getBaseFilter()->apply($query, $input ?? app('request'));
    }
}
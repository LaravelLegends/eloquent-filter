<?php

use LaravelLegends\EloquentFilter\Providers\FilterServiceProvider;
use LaravelLegends\EloquentFilter\Facades\Filter as FilterFacade;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\Rules\Max;
use LaravelLegends\EloquentFilter\Rules\Min;
use Models\Role;

class FilterServiceProviderTest extends Orchestra\Testbench\TestCase
{
    public function testInstanceOf()
    {
        $filter = app(Filter::class);

        $this->assertEquals(get_class($filter), Filter::class);
    }

    public function testFacade()
    {
        $this->assertEquals(FilterFacade::getRule('min'), Min::class);
        $this->assertEquals(FilterFacade::getRule('max'), Max::class);
    }

    public function testApply()
    {
        FilterFacade::apply($query = Role::query(), [
            'contains' => ['name' => 'Admin']
        ]);

        $expected = Role::where(function ($query) {
            $query->where('name', 'LIKE', '%Admin%');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    protected function getPackageProviders($app)
    {
        return [FilterServiceProvider::class];
    }
}
<?php

use LaravelLegends\EloquentFilter\FilterServiceProvider;
use Models\User;

class FilterServiceProviderTest extends Orchestra\Testbench\TestCase
{
    public function testProvider()
    {
        $filter = app(\LaravelLegends\EloquentFilter\Filter::class);

        $this->assertEquals(get_class($filter), \LaravelLegends\EloquentFilter\Filter::class);
    }

    public function testFacade()
    {
        $min = \LaravelLegends\EloquentFilter\Facades\Filter::getRule('min');

        $this->assertEquals($min, \LaravelLegends\EloquentFilter\Rules\Min::class);
    }

    public function testFrom()
    {
        $query = \LaravelLegends\EloquentFilter\Facades\Filter::from(User::class, [
            'exact' => ['name' => 'Wallace']
        ]);

        $expected = User::where(['name' => 'Wallace'])->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    protected function getPackageProviders($app)
    {
        return [FilterServiceProvider::class];
    }
}
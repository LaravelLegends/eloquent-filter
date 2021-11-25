<?php

use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\Providers\FilterServiceProvider;
use Models\User;

class ModelFilterTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [FilterServiceProvider::class];
    }

    public function testGetFilter()
    {
        $this->assertInstanceOf(
            Filter::class,
            (new UserFilter)->getFilter()
        );
    }   

    public function testWithFilter()
    {

        $input = [
            'min'       => ['age' => 18],
            'icontains' => ['name' => 'Wallace'],
            'eq'        => ['id' => 2],
        ];

        $request = request()->replace($input);

        $expected = User::where(function ($query) {
            $query->where('age', '>=', 18);
            $query->where('name', 'ilike', '%Wallace%');
            $query->where('id', '=', 2);
        })->toSql();
        
        $this->assertEquals(
            $expected, 
            User::withFilter(new UserFilter, $request)->toSql()
        );

        $this->assertEquals(
            $expected, 
            User::withFilter(new UserFilter)->toSql()
        );

        $this->assertEquals(
            $expected, 
            User::withFilter(new UserFilter, $input)->toSql()
        );
    }
}

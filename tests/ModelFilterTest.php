<?php

use Models\User;
use Models\UserPhone;
use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\Providers\FilterServiceProvider;

class ModelFilterTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [FilterServiceProvider::class];
    }

    public function testGetBaseFilter()
    {
        $this->assertInstanceOf(
            Filter::class,
            (new UserFilter)->getBaseFilter()
        );
    }

    public function testWithFilter()
    {
        $input = [
            'min'       => ['age' => 18],
            'icontains' => ['name' => 'Wallace'],
            'eq'        => ['id' => 2],
            'exact'     => [ 'phones.number' => 31]
        ];

        $request = request()->replace($input);

        $expected = User::where(function ($query) {
            $query->where('age', '>=', 18);
            $query->where('name', 'ilike', '%Wallace%');
            $query->where('id', '=', 2);
            $query->whereHas('phones', function ($query) {
                $query->where('number', '=', 31);
            });
        })->toSql();
        
        // With request passed
        $this->assertEquals(
            $expected,
            User::withFilter(new UserFilter, $request)->toSql()
        );

        // Without passed $input
        $this->assertEquals(
            $expected,
            User::withFilter(new UserFilter)->toSql()
        );

        // With array input
        $this->assertEquals(
            $expected,
            User::withFilter(new UserFilter, $input)->toSql()
        );
    }

    public function testModelGetFilterable()
    {
        $modelFilter = new CustomFilter([
            'id' => 'exact'
        ]);

        $this->assertEquals(['id' => 'exact'], $modelFilter->getFilterable());
        $this->assertIsArray($modelFilter->getFilterable());
    }

    public function testModelGetFilterableWithParsedRelated()
    {
        // Finge que é um Post de um blog =)

        $modelFilter = new CustomFilter([
            'id' => 'exact',
            'slug' => 'exact',
            'title' => true,
            'tags' => new CustomFilter([
                'id'   => ['exact', 'not_equal'],
                'name' => 'contains'
            ]),
            'author' => new CustomFilter([
                'email' => 'exact',
                'name' => ['contains', 'starts_with']
            ]),
            'author.roles' => new CustomFilter([
                'id' => ['exact', 'not_equal']
            ]),
            'views.count' => ['max', 'min', 'exact'], // manual related
        ]);

        $expected = [
            'id'              => 'exact',
            'slug'            => 'exact',
            'title'           => true,
            'tags.id'         => ['exact', 'not_equal'],
            'tags.name'       => 'contains',
            'author.email'    => 'exact',
            'author.name'     => ['contains', 'starts_with'],
            'author.roles.id' => ['exact', 'not_equal'],
            'views.count'     => ['max', 'min', 'exact'],
        ];

        $this->assertEquals($expected, $modelFilter->getFilterableWithParsedRelations());
    }

    public function testGetDefaultRequest()
    {
        $this->assertInstanceOf(Request::class, (new UserFilter)->getDefaultRequest());
    }

    public function testToClosure1()
    {

        // Constructor args
        
        $arrayInput['not_equal']['id'] = '3';

        $expected = User::where(function ($query) {
            $query->where('id', '<>', '3');
        })->toSql();

        $this->assertEquals(
            $expected,
            User::where(CustomFilter::toClosure($arrayInput, [ 'id' => 'not_equal' ]))->toSql()
        );
    }

    public function testToClosure()
    {
        $arrayInput = [
            'exact' => [
                'code' => '31',
                'number' => '99999999'
            ]
        ];
        
        $request = request()->replace($arrayInput);

        $this->assertInstanceOf(\Closure::class, UserPhoneFilter::toClosure());

        $expected = UserPhone::where(function ($query) {
            $query->where('code', '=', '31');
            $query->where('number', '=', '99999999');
        })->toSql();

        $this->assertEquals($expected, UserPhone::where(UserPhoneFilter::toClosure())->toSql());
        $this->assertEquals($expected, UserPhone::where(UserPhoneFilter::toClosure($request))->toSql());
        $this->assertEquals($expected, UserPhone::where(UserPhoneFilter::toClosure($arrayInput))->toSql());
    }
}

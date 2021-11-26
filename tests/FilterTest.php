<?php

use LaravelLegends\EloquentFilter\Exceptions\RestrictionException;
use LaravelLegends\EloquentFilter\Filter;
use LaravelLegends\EloquentFilter\Rules\Exact;
use Models\User;
use Models\UserPhone;

class FilterTest extends Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }


    public function testGetCallback()
    {
        $callback = (new Filter)->getCallback(request());

        $this->assertTrue($callback instanceof \Closure);
    }


    public function testApplyMax()
    {
        request()->replace(['max' => ['age' => '18']]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('age', '<=', '18');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('18', $query->getBindings());
    }

    public function testApplyMin()
    {
        request()->replace([
            'min' => ['likes' => '500']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('likes', '>=', 500);
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('500', $query->getBindings());
    }

    public function testApplyExact()
    {
        request()->replace([
            'exact' => ['name' => 'wallace', 'active' => '1']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('name', '=', 'wallace')->where('active', '=', 1);
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $bindings = $query->getBindings();

        $this->assertContains('wallace', $bindings);
        $this->assertContains('1', $bindings);
    }

    public function testApplyExactNested()
    {
        request()->replace([
            'exact' => ['phones.number' => '3199999999']
        ]);

        $filter = new Filter();

        // test User.phones
        $filter->apply($user_query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->whereHas('phones', function ($query) {
                $query->where('number', '=', '3199999999');
            });
            
        })->toSql();

        $this->assertEquals(
            $expected,
            $user_query->toSql()
        );

        $this->assertContains('3199999999', $user_query->getBindings());

        // Teste UserPhone.user
        
        request()->replace([
            'exact' => ['user.name' => 'Wallace']
        ]);

        $filter->apply($phone_query = UserPhone::query(), request());

        $expected = UserPhone::where(function ($query) {
            $query->whereHas('user', function ($query) {
                $query->where('name', '=', 'Wallace');
            });
        })->toSql();


        $this->assertEquals(
            $expected,
            $phone_query->toSql()
        );

        $this->assertContains('Wallace', $phone_query->getBindings());
    }


    public function testApplyContains()
    {
        request()->replace([
            'contains' => ['name' => 'wallace']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('name', 'LIKE', '%Wallace%');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('%wallace%', $query->getBindings());
    }

    public function testApplyEndsWith()
    {
        request()->replace([
            'ends_with' => ['name' => 'guilherme']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('name', 'LIKE', '%guilherme');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $bindings = $query->getBindings();
        
        $this->assertContains('%guilherme', $bindings);
    }
    

    public function testApplyStartsWith()
    {
        request()->replace([
            'starts_with' => ['nick' => 'brcontainer']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::where(function ($query) {
            $query->where('nick', 'LIKE', 'brcontainer%');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('brcontainer%', $query->getBindings());
    }


    public function testApplyHas()
    {
        request()->replace([
            'has' => ['phones' => '1', 'documents' => '0']
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected_sql = User::where(static function ($query) {
            $query->has('phones')->doesntHave('documents');
        })->toSql();

        $this->assertEquals($query->toSql(), $expected_sql);
    }


    public function testApplyIsNull()
    {
        request()->replace([
            'is_null' => ['deleted_at' => '1', 'cpf' => '0']
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected = User::where(function ($query) {
            $query->whereNull('deleted_at')->whereNotNull('cpf');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertTrue(count($query->getBindings()) === 0);
    }

    public function testApplyIn()
    {
        request()->replace([
            'in' => ['role_id' => ['1', '2']]
        ]);


        (new Filter)->apply($query = User::query(), request());

        $expected_sql = User::where(function ($query) {
            $query->whereIn('role_id', ['1', '2']);
        })->toSql();

        $this->assertEquals($query->toSql(), $expected_sql);
        
        $bindings = $query->getBindings();

        $this->assertContains('1', $bindings);
        $this->assertContains('2', $bindings);
    }


    public function testApplyNotIn()
    {
        request()->replace([
            'not_in' => ['name' => ['wallacemaxters', 'brcontainer']],
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected = User::where(static function ($query) {
            $query->whereNotIn('name', ['wallacemaxters', 'brcontainer']);
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
        
        $bindings = $query->getBindings();

        $this->assertContains('wallacemaxters', $bindings);
        $this->assertContains('brcontainer', $bindings);
    }


    public function testApplyNotInAndIn()
    {
        request()->replace([
            'in'     => ['role' => ['1', '2']],
            'not_in' => ['name' => ['wallacemaxters', 'brcontainer']],
        ]);

        $query = User::query();

        $expected = User::where(function ($query) {
            $query->whereIn('role', ['1', '2']);
            $query->whereNotIn('name', ['wallacemaxters', 'brcontainer']);
        })->toSql();

        (new Filter())->apply($query, request());

        $this->assertEquals($expected, $query->toSql());
    }
    
    public function testSetRule()
    {
        request()->replace([
            'between' => ['age' => [18, 65]],
            'eq'      => ['id' => 5]
        ]);

        $query = User::query();

        (new Filter)->setRule('between', function ($query, $field, $value) {
            $query->where($field, '>=', $value[0])->where($field, '<=', $value[1]);
        })
        ->setRule('eq', Exact::class)
        ->apply($query, request());

        $expected = User::where(function ($query) {
            $query->where('age', '>=', 18)->where('age', '<=', 65)->where('id', '=', 5);
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    public function testSetRuleCatchException()
    {
        $filter = new Filter();
        try {
            $filter->setRule('rule', 'invalid_value');
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'The rule should be callable or instance of LaravelLegends\EloquentFilter\Contracts\ApplicableFilter');
            $this->assertTrue($e instanceof \UnexpectedValueException);
        }
    }


    public function testDateMax()
    {
        request()->replace([
            'date_max' => ['posted_at' => '2025-12-30']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected_sql = User::where(function ($query) {
            $query->whereDate('posted_at', '<=', '2025-12-30');
        })->toSql();

        $this->assertEquals($query->toSql(), $expected_sql);

        $this->assertContains('2025-12-30', $query->getBindings());
    }

    public function testDateMin()
    {
        request()->replace([
            'date_min' => ['posted_at' => '1998-10-10']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected_sql = User::where(function ($query) {
            $query->whereDate('posted_at', '>=', '1998-10-10');
        })->toSql();

        $this->assertEquals($query->toSql(), $expected_sql);

        $this->assertContains('1998-10-10', $query->getBindings());
    }

    public function testDateExact()
    {
        request()->replace([
            'date_exact' => ['updated_at' => '2021-01-01']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::query()->where(function ($query) {
            $query->whereDate('updated_at', '=', '2021-01-01');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    public function testNotEqual()
    {
       $request = request();
       
       $request->replace(['not_equal' => ['profile_id' => '3']]);

        (new Filter)->apply($query = User::query(), $request);

        $expected = User::where(function ($q) {
            $q->where('profile_id', '<>', '3');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('3', $query->getBindings());
    }

    public function testMaxYear()
    {
        request()->replace([
            'year_max' => ['created_at' => '2021']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::query()->where(function ($query) {
            $query->whereYear('created_at', '<=', '2021');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    public function testMinYear()
    {
        request()->replace([
            'year_min' => ['created_at' => '2021']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::query()->where(function ($query) {
            $query->whereYear('created_at', '=>', '2021');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    public function testExactYear()
    {
        request()->replace([
            'year_exact' => ['created_at' => '2021']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected = User::query()->where(function ($query) {
            $query->whereYear('created_at', '=', '2021');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }

    public function testWithoutNested()
    {
        request()->replace([
            'not_equal' => ['profile_id' => '7']
        ]);

        (new Filter)->applyWithoutNested($query = User::query(), request());

        $expected = User::where('profile_id', '<>', '7')->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('7', $query->getBindings());
    }


    public function testTraitHasFilter()
    {

        // api/users?contains[name]=Wallace
        request()->replace([
            'contains' => ['name' => 'Wallace'],
        ]);

        $query = User::filter();

        $expected = User::where(function ($query) {
            $query->where('name', 'LIKE', '%Wallace%');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());
    }


    public function testSetFilterable()
    {

        // api/users?contains[name]=Wallace
        request()->replace([
            'contains' => ['name' => 'Wallace'],
        ]);

        $query = User::filter();

        $expected = User::where(function ($query) {
            $query->where('name', 'LIKE', '%Wallace%');
        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        request()->replace([
            'exact' => ['name' => 'Wallace'],
        ]);

        try {
            $query = User::filter();
        } catch (RestrictionException $e) {
            $this->assertEquals($e->getMessage(), 'Cannot use filter "name" field with rule "exact"');
        }

    
        request()->replace([
            'max' => ['name' => 'Wallace'],
        ]);

        $filter = (new Filter)->setFilterable(['name' => ['contains']]);

        try {
            $filter->apply(User::query(), request());
        } catch (RestrictionException $e) {
            $this->assertEquals($e->getMessage(), 'Cannot use filter "name" field with rule "max"');
        }

        $filter->allowAllFilterables()->apply(User::query(), request());
        // no exception
    }


    public function testRelationParse()
    {
        $request = request();

        $request->replace([
            'contains'  => ['phones.number'  => '4321', 'phones.ddd' => '32'],
            'exact'     => ['phones.country' => '55'],
            'not_equal' => ['roles.id'       => '2'],
        ]);

        $expected = User::where(static function ($query) {

            // contains
            $query->whereHas('phones', static function ($query) {
                $query->where('number', 'LIKE', '%4321%')->where('ddd', 'LIKE', '%32%');
            });

            // exact 
            $query->whereHas('phones', static function ($query) {
                $query->where('country', '=', 55);
            });

            // not_equal
            $query->whereDoesntHave('roles', function ($query) {
                $query->where('id', '=', 2);
            });
            
        })->toSql();

        (new Filter)->apply($query = User::query(), $request);

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('%4321%', $bindings = $query->getBindings());
        $this->assertContains('%32%', $bindings);
        $this->assertContains('55', $bindings);
        $this->assertContains('2', $bindings);
    }


    public function testSetDataCallback()
    {
        $filter = new Filter();

        // the request /api/users?phones.country=exact:55&email=contains:31
        $filter->setDataCallback(static function ($rule, $key, $value) {
            $expr = $rule . ':';
            $pos = strpos($value, $expr);
            if ($pos === 0) {
                return [$key, substr($value, strlen($expr))];
            }
        });
        
        request()->replace([
            'phones.country'  => 'exact:55',
            'email' => 'contains:31',
        ]);


        $filter->apply($query = User::query(), request());

        $bindings = $query->getBindings();

        $this->assertContains('55', $bindings);
        $this->assertContains('%31%', $bindings);
    }

    public function testApplyFromArray()
    {
        (new Filter)->apply($query = User::query(), [
            'exact' => [
                'phones.country' => 31
            ],
            'contains' => [
                'name' => 'Wallace'
            ]
        ]);

        $expected = User::where(function ($query) {

            $query->where('name', 'LIKE', '%Wallace%');

            $query->whereHas('phones', function ($query) {
                $query->where('country', 31);
            });

        })->toSql();

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('%Wallace%', $query->getBindings());
        $this->assertContains(31, $query->getBindings());

    }

    public function testGetCallbackFromArray()
    {
        $callback = (new Filter)->getCallbackFromArray([
            'exact' => [
                'age'            => 99,
                'phones.country_code' => 55
            ],
            'starts_with' => [
                'last_name' => 'Vizerra'
            ]
        ]);

        $expected = User::where(function ($query) {

            $query->where('age', '=', 99);
            $query->where('last_name', 'LIKE', 'Vizerra%');

            $query->whereHas('phones', function ($query) {
                $query->where('country_code', 55);
            });

        })->toSql();

        $query = User::where($callback);

        $this->assertEquals($expected, $query->toSql());

        $this->assertContains('Vizerra%', $query->getBindings());
        $this->assertContains(55, $query->getBindings());
        $this->assertContains(99, $query->getBindings());

    }

    public function testGetCallbackFromRequest()
    {
        $request = request();

        $request->replace([
            'exact'       => ['age' => 99 ],
        ]);

        $callback = (new Filter)->getCallbackFromRequest($request);

        $expected = User::where(function ($query) {
            $query->where('age', '=', 99);
        })->toSql();

        $query = User::where($callback);

        $this->assertEquals($expected, $query->toSql());
        
        $this->assertContains(99, $query->getBindings());

    }

    public function testRelationFilterMethods()
    {
        $request = request();

        $request->replace([
            'not_equal' => ['roles.disabled' => '1'],
            'not_in'    => ['roles.name' => ['Owner', 'Admin']],
        ]);

        $expected = User::where(static function ($query) {
            // not_equal
            $query->whereDoesntHave('roles', function ($query) {
                $query->where('disabled', '=', '1');
            });

            // not_in
            $query->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Admin']);
            });
        })->toSql();

        (new Filter)->apply($query = User::query(), $request);

        $this->assertEquals($expected, $query->toSql());
    }
}
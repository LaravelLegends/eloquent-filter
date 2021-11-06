<?php

use LaravelLegends\EloquentFilter\Exceptions\RestrictionException;
use LaravelLegends\EloquentFilter\Filter;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

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

        $this->assertTrue($query->toSql() === 'select * from "users" where ("likes" >= ?)');

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

        $this->assertEquals(
            $user_query->toSql(),
            'select * from "users" where (exists (select * from "user_phones" where "users"."id" = "user_phones"."user_id" and "number" = ?))'
        );

        $this->assertContains('3199999999', $user_query->getBindings());

        // Teste UserPhone.user
        
        request()->replace([
            'exact' => ['user.name' => 'Wallace']
        ]);

        $filter->apply($phone_query = UserPhone::query(), request());


        $this->assertEquals(
            $phone_query->toSql(),
            'select * from "user_phones" where (exists (select * from "users" where "user_phones"."user_id" = "users"."id" and "name" = ?))'
        );

        $this->assertContains('Wallace', $phone_query->getBindings());
    }


    public function testApplyContains()
    {
        request()->replace([
            'contains' => ['name' => 'wallace']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $this->assertTrue($query->toSql() === 'select * from "users" where ("name" LIKE ?)');

        $bindings = $query->getBindings();

        $this->assertContains('%wallace%', $bindings);
    }

    public function testApplyEndsWith()
    {
        request()->replace([
            'ends_with' => ['name' => 'guilherme']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $this->assertTrue($query->toSql() === 'select * from "users" where ("name" LIKE ?)');

        $bindings = $query->getBindings();
        
        $this->assertContains('%guilherme', $bindings);
    }
    

    public function testApplyStartsWith()
    {
        request()->replace([
            'starts_with' => ['nick' => 'brcontainer']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $this->assertTrue($query->toSql() === 'select * from "users" where ("nick" LIKE ?)');

        $this->assertContains('brcontainer%', $query->getBindings());
    }


    public function testApplyHas()
    {
        request()->replace([
            'has' => ['phones' => '1', 'documents' => '0']
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected_sql = 'select * from "users" where (exists (select * from "user_phones" where "users"."id" = "user_phones"."user_id") and not exists (select * from "user_documents" where "users"."id" = "user_documents"."user_id"))';

        $this->assertTrue($query->toSql() === $expected_sql);

        $this->assertTrue(count($query->getBindings()) === 0);
    }


    public function testApplyIsNull()
    {
        request()->replace([
            'is_null' => ['deleted_at' => '1', 'cpf' => '0']
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected_sql = 'select * from "users" where ("deleted_at" is null and "cpf" is not null)';

        $this->assertTrue($query->toSql() === $expected_sql);

        $this->assertTrue(count($query->getBindings()) === 0);
    }

    public function testFromModel()
    {
        request()->replace([
            'max'      => ['age' => '18'],
            'contains' => ['name' => 'wallace'],
        ]);

        $query = Filter::fromModel(User::class, request());

        $this->assertTrue(
            $query->toSql() == 'select * from "users" where ("age" <= ? and "name" LIKE ?)'
        );

        $bindings = $query->getBindings();

        $this->assertContains('%wallace%', $bindings);
        $this->assertContains('18', $bindings);
    }


    public function testApplyIn()
    {
        request()->replace([
            'in' => ['role_id' => ['1', '2']]
        ]);


        (new Filter)->apply($query = User::query(), request());

        $expected_sql = 'select * from "users" where ("role_id" in (?, ?))';

        $this->assertTrue($query->toSql() === $expected_sql);
        
        $bindings = $query->getBindings();

        $this->assertContains('2', $bindings);
        $this->assertContains('1', $bindings);
    }


    public function testApplyNotIn()
    {
        request()->replace([
            'not_in' => ['name' => ['wallacemaxters', 'brcontainer']],
        ]);

        $query = User::query();

        (new Filter())->apply($query, request());

        $expected_sql = 'select * from "users" where ("name" not in (?, ?))';

        $this->assertTrue($query->toSql() === $expected_sql);
        
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

        (new Filter())->apply($query, request());

        $expected_sql = 'select * from "users" where ("role" in (?, ?) and "name" not in (?, ?))';

        $this->assertTrue($query->toSql() === $expected_sql);
    }
    
    public function testSetRule()
    {
        request()->replace([
            'between' => ['age' => [18, 65]]
        ]);

        $query = User::query();

        (new Filter)->setRule('between', function ($query, $field, $value) {
            $query->where($field, '>=', $value[0])->where($field, '<=', $value[1]);
        })
        ->apply($query, request());

        $expected_sql = 'select * from "users" where ("age" >= ? and "age" <= ?)';
    
        $this->assertEquals($query->toSql(), $expected_sql);

        // $this->assertContains('2022-12-31', $query->getBindings());
    }

    public function testSetRuleCatchException()
    {
        $filter = new Filter();
        try {
            $filter->setRule('rule', 'invalid_value');
        } catch (\Exception $e) {
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
        request()->replace(['not_equal' => ['profile_id' => '3']]);

        (new Filter)->apply($query = User::query(), request());

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

        $this->assertEquals($query->toSql(), 'select * from "users" where "profile_id" <> ?');

        $this->assertContains('7', $query->getBindings());
    }


    public function testTraitHasFilter()
    {

        // api/users?contains[name]=Wallace
        request()->replace([
            'contains' => ['name' => 'Wallace'],
        ]);

        $query = User::filter();

        $this->assertEquals($query->toSql(), 'select * from "users" where ("name" LIKE ?)');
    }


    public function testAllow()
    {

        // api/users?contains[name]=Wallace
        request()->replace([
            'contains' => ['name' => 'Wallace'],
        ]);

        $query = User::filter();

        $this->assertEquals($query->toSql(), 'select * from "users" where ("name" LIKE ?)');

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

        $filter = (new Filter)->allow(['name' => ['contains']]);

        try {
            $filter->apply(User::query(), request());
        } catch (RestrictionException $e) {
            $this->assertEquals($e->getMessage(), 'Cannot use filter "name" field with rule "max"');
        }

        $filter->allowAll()->apply(User::query(), request());
        // no exception
    }


    public function testRelationParse()
    {
        request()->replace([
            'exact'    => ['phones.country' => '55'],
            'contains' => ['phones.number' => '4321', 'phones.ddd' => '32']
        ]);

        (new Filter)->apply($query = User::query(), request());

        $expected_sql = 'select * from "users" where (exists (select * from "user_phones" where "users"."id" = "user_phones"."user_id" and "number" LIKE ? and "ddd" LIKE ? and "country" = ?))';

        $this->assertEquals($query->toSql(), $expected_sql);

        $this->assertContains('%4321%', $bindings = $query->getBindings());
        $this->assertContains('%32%', $bindings);
        $this->assertContains('55', $bindings);
    }


    public function testSetDataCallback()
    {
        $filter = new Filter();

        // the request /api/users?phones.country=exact:55&email=contains:31
        $filter->setDataCallback(function ($rule, $key, $value) {
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

    public function testFrom()
    {
        $data = [
            'min' => ['age' => 18]
        ];

        $actual1 = (new Filter)->from(User::class, $data)->toSql();

        $expected1 = User::where(function ($query) {
            $query->where('age', '>=', 18);
        })->toSql();

        $this->assertEquals($expected1, $actual1);

        $request = request();
        $request->replace($data + ['exact' => ['email' => 'wallacemaxters@gmail.com']]);

        $expected2 = User::where(function ($query) {
            $query->where('age', '>=', 18)->where('email', 'wallacemaxters@gmail.com');
        })->toSql();

        $actual2 = (new Filter)->from(new User, $request)->toSql();

        $this->assertEquals($expected2, $actual2);

    }
}



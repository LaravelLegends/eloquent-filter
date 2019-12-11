<?php

use LaravelLegends\EloquentFilter\Filter;

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

		$filter = new Filter();

		$query = User::query();

		$callback = $filter->getCallback(request());

		$this->assertTrue($callback instanceof \Closure);
	}


	public function testApplyMax()
	{
		$query = User::query();

		request()->replace([
			'max' => ['age' => '18']
		]);

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("age" <= ?)');

		$this->assertContains('18', $query->getBindings());
		
	}

	public function testApplyMin()
	{
		request()->replace([
			'min' => ['likes' => '500']
		]);

		$query = User::query();

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("likes" >= ?)');

		$this->assertContains('500', $query->getBindings());
	}

	public function testApplyExact()
	{
		request()->replace([
			'exact' => ['name' => 'wallace', 'active' => '1']
		]);

		$query = User::query();

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("name" = ? and "active" = ?)');

		$bindings = $query->getBindings();

		$this->assertContains('wallace', $bindings);
		$this->assertContains('1', $bindings);
	}


	public function testApplyContains()
	{
		request()->replace([
			'contains' => ['name' => 'wallace']
		]);

		$query = User::query();

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("name" LIKE ?)');

		$bindings = $query->getBindings();

		$this->assertContains('%wallace%', $bindings);
	}

	public function testApplyEndsWith()
	{
		request()->replace([
			'ends_with' => ['name' => 'guilherme']
		]);

		$query = User::query();

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("name" LIKE ?)');

		$bindings = $query->getBindings();
		
		$this->assertContains('%guilherme', $bindings);
	}
	

	public function testApplyStartsWith()
	{
		request()->replace([
			'starts_with' => ['nick' => 'brcontainer']
		]);

		$query = User::query();

		(new Filter())->apply($query, request());

		$this->assertTrue($query->toSql() === 'select * from "users" where ("nick" LIKE ?)');

		$bindings = $query->getBindings();
		
		$this->assertContains('brcontainer%', $bindings);
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


	public function testGetPrefix()
	{
		$filter = new Filter();

		$this->assertTrue($filter->getPrefix() === null);

		$filter->setPrefix('_');

		$this->assertTrue($filter->getPrefix() === '_');
	}

	public function testApplyWithSetPrefix()
	{
		$request = request();
		
		$request->replace([
			'_max'      => ['age' => '18'],
			'_contains' => ['name' => 'wallace'],
		]);

		$query = User::query();

		(new Filter())->setPrefix('_')->apply($query, $request);

		$this->assertTrue(
			$query->toSql() == 'select * from "users" where ("age" <= ? and "name" LIKE ?)'
		);


		$bindings = $query->getBindings();

		$this->assertContains('%wallace%', $bindings);
		$this->assertContains('18', $bindings);
	}
}
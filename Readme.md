# Laravel Legends Eloquent Filter

A useful library to make filters for Eloquent.

This library is useful to create search filters in your Rest API using the Eloquent.

🇧🇷🚀🚀🚀


## Description

The Eloquent Filter library can be used to create patterns for search criterias on models in your Laravel Project. The idea is aggregate filters simply passing the values in your request payload.

## Instalation

For instalation, you should be use Composer. Run the follow command: 

```composer require laravellegends/eloquent-filter```


## Usage guide

The `LaravelLegends\EloquentFilter\Concerns\HasFilter` trait can be used in models that will be apply the search filters. 

```php
use LaravelLegends\EloquentFilter\Concerns\HasFilter;

class User extends Model
{
    use HasFilter;
}
```

The `HasFilter` trait provides the `filter` and `withFilter` methods. 

A simple way to use this library in your Laravel application is calling the `filter` method before get results of your model. 


Example:

```php
class UsersController extends Controller 
{
    use App\Models\User;
    
    public function index()
    {
        return User::filter()->paginate();
    }

    // or

    public function index()
    {
        return User::latest('id')->filter()->paginate();
    }

    // or

    public function index(Request $request)
    {
        return User::filter($request)->paginate();
    }
}
```

You can show the results when call `/api/users?exact[id]=1`. The sql query `"select * from users where (id = 1)"` will be applied.

Note: Show the [rules](#max) session to more information.

Another way, is using the specific filter for a model. You can inherit the ModelFilter class to create a custom filter for a model.

For create this class, you should be use the command `php artisan make:filter`, as follow example:

```bash
$ php artisan make:filter UserFilter
```

The above command will be generate the follow class:

```php
namespace App\Filters;

use LaravelLegends\EloquentFilter\Filters\ModelFilter;

class UserFilter extends ModelFilter 
{
    public function getFilterables(): array
    {
        return [
            'role_id' => 'not_equal', // or ['not_equal']
            'name'    => ['contains', 'starts_with'],
        ];
    }
}
```

In Controller

```php
use App\Models\User;
use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Filter;

class UsersController extends Controller
{
    // api/users?starts_with[name]=Wallace&not_equal[role_id]=2

    public function index(Request $request)
    {
        return User::withFilter(new UserFilter, $request)
                    ->orderBy('name')
                    ->get();
    }
}
```

The above code internally will be called as follow example:

```php

User::where(function ($query) {
    $query->where('name', 'LIKE', 'Wallace%');
    $query->where('role_id', '<>', '2');
})
->orderBy('name')
->get();
```

----

## What does it do?

This library internally apply filters based on query string parameters with special keyworks names.

See all paramaters follow:

## max
The maximum value of a column. The url `api/users?max[field]=100` is like a `User::where('field', '<=', 100)`.

----

## min
The minimum value of a column. The url `api/users?min[age]=33` is like a `User::where('age', '>=', 33)`.

----

## contains
A search term contained in a column.
The url `api/users?contains[name]=wallace` is like a `User::where('name', 'LIKE', '%wallace%')`.

----

## ends_with

Search a value according to end content of string. Is similar to a `LIKE` with `%$value` value.

----

## starts_with

Filter the field when the value starts with a certain value.
A url `api/users?starts_with[name]=brcontainer` Sounds like a  `User::where('name', 'LIKE', 'brcontainer%')`.

----

## exact
Search by a exact value of the field·
A url `api/users?exact[email]=teste@teste.com` Sounds like a  `User::where('name', '=', 'teste@teste.com')`.

----

## has

Filter by relationship. You can use the `0` or `1` value.

Example:

The url `api/users?has[posts]=1` is like a `User::has('posts')`

The url `api/users?has[posts]=0` is like a `User::doesntHave('posts')`

----
## is_null

Apply `WHERE IS NULL` or `WHERE IS NOT NULL` to a query.

Example:

The url `api/users?is_null[cpf]=1` is like a `User::whereNull('cpf')`

The url `api/users?is_null[age]=0` is like a `User::whereNotNull('age')`

----
## not_in

Searchs when a column NOT HAS the passed values.

Example:

A url `api/users?not_in[role][]=1&not_in[role][]=2` é equivalente à `User::whereNotIn('role', [1, 2])`

**Note**: When the `not_in[my_field]` is a empty array, no action will be taken.

----
## in

Searchs when a column HAS the passed values.

Example:

The url `api/users?in[role][]=10&in[role][]=20` sounds like a `User::whereIn('role', [10, 20])`

**NOTE**: When the `in[my_field]` is a empty array, no action will be taken.

----

## date_max
Search by a maximium value of a date field.

A url `api/users?date_max[created_at]=2021-01-01` sounds like a `User::whereDate('created_at', '<=', '2021-01-01')`

----

## date_min

Search by a minimun value of a date field.

Example:

A url `api/users?date_min[created_at]=2021-01-01` sounds like a `User::whereDate('created_at', '>=', '2021-01-01')`

----

## not_equal

Search by not equal value passed. If you use in related field, the whereDoesntHave will be applied applied.

Example:

The url `api/users?not_equal[profile_id]=3` sounds like a 

```php
User::where('profile_id', '<>', '3');
```

The url `api/users?not_equal[roles.id]=1` sounds like a 

```php
User::whereDoesntHave('roles', fn ($query) => $query->where('id', '=', 3));
```

----

## year_max

The url `api/users?year_max[created_at]=2000` sounds like a

```php
User::whereYear('created_at', '<=', 2000);
```

----

## year_min

The url `api/users?year_min[created_at]=1998` sounds like a

```php
User::whereYear('created_at', '>=', 1998);
```

----

## year_exact


The url `api/users?year_exact[created_at]=1998` sounds like a

```php
User::whereYear('created_at', '=', 1998);
```

----

## Filtering relationship fields

You can apply the search filters in the relatioship methods defined in your model.

For example:

Model:

```php
class User extends Model
{
    use HasFilter;

    public function phones()
    {
        return $this->hasMany(Phone::class, 'user_id');
    }
}
```

Filters:

```php
class UserFilter extends ModelFilter
{
    public function getFilterables(): array
    {
        return [
            'id'            => ['exact', 'not_equal'],
            'created_at'    => ['year_exact', 'date_max', 'date_min'],
            'phones.number' => ['contains'],
            // or
            'phones'        => new PhoneFilter,
        ];
    }
}

class PhoneFilter extends ModelFilter
{

    public function getFilterables(): array
    {
        return [
            'number' => 'contains'
        ];
    }
}
```

```php
class UserController extends Controller
{
    public function index()
    {
        // api/users?not_in[role_id][]=1&not_in[role_id][]=3
        
        // select * from users where (role_id NOT IN (1, 3))

        return User::withFilter(new UserFilter)->paginate();
    }

    // Or, apply filter as nested query

    public function index() 
    {

        // api/users?exact[role_id]=1
        
        // select * from users where (role_id = 1)

        return User::where(UserFilter::toClosure())->paginate();
    }

    // Or apply in your query as base condition

    public function index()
    {
        
        // api/users?exact[role_id]=1
        
        // select * from users where role_id = 1

        return User::tap(UserFilter::toClosure())->paginate();
    }
}
```

In the following example, the user will be filtered for the related phone containing the value `55`.

The ```api/users?exact[phones.number]=55``` is like to:

```php
User::where(function ($query) {
    $query->whereHas('phones', function ($query) {
        $query->where('number', '=', '55');
    });
})->paginate();
```

## Axios examples

If you use `axios` library, you can use the `params` options to include the above filters.

Example:

```javascript
const api = axios.create({
    baseURL: 'http://localhost:8000/api'
});

api.get('users', {
    params: { 
        'in[role]' : [1, 2, 3],
        'contains[name]' : 'Maxters',
        'is_null[name]' : 0
    }
})
```

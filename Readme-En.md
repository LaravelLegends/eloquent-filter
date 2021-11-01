# Eloquent Filter

A useful library to make standards in filters in Eloquent.
A very useful library for creating and standardizing search filters in Laravel Eloquent.

🇧🇷🚀🚀🚀


## Description

This library is helpful to create standard to search filters for Laravel. The idea is aggregate many filters simply passing the values in your requests. Furthermore, this library helps you to avoid write or rewrite many lines of code for create search on your requests.

## Instalation

Run the follow command 

```composer require laravellegends/eloquent-filter```

## Usage examples:

You can use the Eloquent Filter with two ways:

### Using the `HasFilter` trait

The `LaravelLegends\EloquentFilter\HasFilter` trait can be used in models that will be apply the search filters. 
This trait provides the `filter` method for model.
 
#### Example
Model:
```php
use LaravelLegends\EloquentFilter\HasFilter;

class User extends Model
{
    use HasFilter;
}
```

Controller:


```php
class UsersController extends Controller {

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

### Using the `Filter` class

Yo can also use the `Filter` directly.

See:

```php
use App\Models\User;
use Illuminate\Http\Request;
use LaravelLegends\EloquentFilter\Filter;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = Filter::fromModel(User::class, $request);

        return $query->paginate();
    }

    // or

    public function index(Request $request)
    {
        $query = User::orderBy('name');

        (new Filter)->apply($query, $request);

        return $query->paginate();
    }
}
```
Note that in second example, is required to pass a `Request` instance as argument. It is a very useful in cases where you need to use a custom `Request` instance (made by `artisan make:request` command).

## What does it do?
Ao utilizar um dos exemplos acima, você pode fazer a seguinte chamada: `api/users?contains[name]=search+term`
Ao fazer isso, o `Filter` esperará a passagem de parâmetros específicos para realizar filtros padrão na sua consulta.

Por exemplo, `contains` representa a regra de filtro utilizado internamente. `name` trata-se do campo onde o filtro será aplicado. `search+term` trata-se o valor desse campo.

Veja a relação de parâmetros que podem ser utilizados na consulta:

## `max`

The maximum value of a column. The url `api/users?max[field]=100` is like a `User::where('field', '<=', 100)`.

## `min`
The minimum value of a column. The url `api/users?min[age]=33` is like a `User::where('age', '>=', 33)`.

## `contains`
A search term contained in a column.
The url `api/users?contains[name]=wallace` is like a `User::where('name', 'LIKE', '%wallace%')`.

## `ends_with`
Search a value according to end content of string. Sounds like a `LIKE` with `%$value` value.

## `starts_with`

Filtra a coluna contendo determinado valor no início. 

A url `api/users?starts_with[name]=brcontainer` Sounds like a  `User::where('name', 'LIKE', 'brcontainer%')`.

## `exact`
Filtra a coluna por um valor exato.

A url `api/users?exact[email]=teste@teste.com` Sounds like a  `User::where('name', '=', 'teste@teste.com')`.

## `has`

Filter by relationship. You can use the `0` or `1` value.

Example:

The url `api/users?has[posts]=1` is like a `User::has('posts')`

The url `api/users?has[posts]=0` is like a `User::doesntHave('posts')`



## `is_null`

Apply `WHERE IS NULL` or `WHERE IS NOT NULL` to a query.

Example:

The url `api/users?is_null[cpf]=1` is like a `User::whereNull('cpf')`

The url `api/users?is_null[age]=0` is like a `User::whereNotNull('age')`



## `not_in`

Filtra quando multiplos valores não estão presente numa coluna. 

Example:

A url `api/users?not_in[role][]=1&not_in[role][]=2` é equivalente à `User::whereNotIn('role', [1, 2])`

**Observação**: Quando o `not_in[my_field]` for equivalente a um array vazio, nada será executado.


## `in`

Filtra quando multiplos valores estão presentes numa coluna. 

Example:

A url `api/users?in[role][]=10&in[role][]=20` é equivalente à `User::whereIn('role', [10, 20])`

**Observação**: Quando o `in[my_field]` for equivalente a um array vazio, nada será executado.

## `date_max`
Filtrar uma data através de um valor máximo.

A url `api/users?date_max[created_at]=2021-01-01` é equivalente a `User::whereDate('created_at', '<=', '2021-01-01')`

## `date_min`

Filtra um campo de data através de um valor mínimo.

Example:

A url `api/users?date_min[created_at]=2021-01-01` é equivalente a `User::whereDate('created_at', '>=', '2021-01-01')`


## `not_equal`

Aplica um filtro utilizando o operador "não igual".

Example:

A url `api/users?not_equal[profile_id]=3` é equivalente a `User::where('profile_id', '<>', '3')`


## Filtering relationship fields

É possível aplicar os filtros de pesquisa desta biblioteca nos relacionamentos definidos no seu Model.

Por Example:

```php
class User
{
    use HasFilter;

    public function phones()
    {
        return $this->hasMany(Phone::class, 'user_id');
    }
}

```

Você poderia buscar os usuários que possua um telefone com código do país como 55. É necessário apenas chamar o método relacionado ao model mais o campo, separado por ponto.

Veja:

```api/users?exact[phones.number]=55```

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


## Fields restriction

You can configure the filters for specific fields. You need only to pass an `array` with the follow rules:

```php
[
    'name' => 'contains' // Only "contains" for "name" field,
    'created_at' => ['date_min', 'date_max'] // Allow only two specified filters for the "created_at" field,
    'phones.number' => true, // Accepts all filter rules for "number" field of "phones()" relationship
    'profile_id'  => '*' // Accepts all filter rules  for "profile_id" field
]
```

### Restricting fields that will be filtered in the model

To apply restrictions on certain filter that will be filtered, you can set the `$allowedFilters` property with the follow rules:

```php
use LaravelLegends\EloquentFilter\HasFilter;

class User extends Model
{
    use HasFilter;

    protected $allowedFilters = [
        'name'         => 'contains',
        'phone.number' => 'contains',
        'price'        => ['max', 'min'],
        'profile_id'   => '*',
    ];
}
```


### Using the allow method

```php
$alloweds = [
    'name' => 'contains'
];

$query = User::query();

(new Filter)->allow($alloweds)->apply($query, $request)
```

### Using the Filter::fromModel

```php
$allowed = [
    'name' => 'contains'
];

$query = Filter::fromModel(User::class, $request, $allowed);

return $query->paginate();
```

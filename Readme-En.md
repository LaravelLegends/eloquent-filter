# Eloquent Filter
A useful library to make standards in filters in Eloquent.
A very useful library for creating and standardizing search filters in Laravel Eloquent.

## Description

This library is helpful to create standard to search filters for Laravel. The idea is aggregate many filters simply passing the values in your requests. Furthermore, this library helps you to avoid write or rewrite many lines of code for create search on your requests.

## Instalation

Run the follow command 

```composer require laravellegends/eloquent-filter```

## Usage examples:

Existem duas maneiras de utilizar a biblioteca Eloquent Filter. 

### Using the `HasFilter` trait

O trait `LaravelLegends\EloquentFilter\HasFilter` pode ser utilizado no model onde você deseja aplicar os filtros. Ao adicionar o `trait`, o método `filter` estará disponível.
 

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

    // ou

    public function index()
    {
        return User::latest('id')->filter()->paginate();
    }

    // ou

    public function index(Request $request)
    {
        return User::filter($request)->paginate();
    }
}
```

### Using the `Filter` class

Você também pode utilizar a classe `Filter` diretamente para aplicar em suas consultas com eloquent.

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

    // ou

    public function index(Request $request)
    {
        $query = User::orderBy('name');

        (new Filter)->apply($query, $request);

        return $query->paginate();
    }
}
```

Note que, no segundo exemplo, precisamos passar uma instância de `Request`. Isso é muito útil em casos onde você queria utilizar as requests criadas por `make:request`.


## What does it do?
Ao utilizar um dos exemplos acima, você pode fazer a seguinte chamada: `api/users?contains[name]=search+term`
Ao fazer isso, o `Filter` esperará a passagem de parâmetros específicos para realizar filtros padrão na sua consulta.

Por exemplo, `contains` representa a regra de filtro utilizado internamente. `name` trata-se do campo onde o filtro será aplicado. `search+term` trata-se o valor desse campo.

Veja a relação de parâmetros que podem ser utilizados na consulta:

## `max`

o valor máximo da coluna. A url `api/users?max[field]=100` equivalente a `User::where('field', '<=', 100)`.

## `min`
O valor mínimo da coluna. A url `api/users?min[age]=33` equivalente a `User::where('age', '>=', 33)`.

## `contains`
Um termo contindo numa coluna.
A url `api/users?contains[name]=wallace` é equivalente a `User::where('name', 'LIKE', '%wallace%')`.

## `ends_with`
Filtra a coluna contendo determinado valor no final. Utiliza internamente um `LIKE` com o valor `%$value`.

## `starts_with`

Filtra a coluna contendo determinado valor no início. 

A url `api/users?starts_with[name]=brcontainer` é equivalente a  `User::where('name', 'LIKE', 'brcontainer%')`.

## `exact`
Filtra a coluna por um valor exato.

A url `api/users?exact[email]=teste@teste.com` é equivalente a  `User::where('name', '=', 'teste@teste.com')`.

## `has`

Filtra através de um relacionamento. Você pode usar o valor `0` ou `1`.

Exemplo:

A url `api/users?has[posts]=1` é equivalente à `User::has('posts')`

A url `api/users?has[posts]=0` é equivalente à `User::doesntHave('posts')`



## `is_null`

Filtra se o valor for `null` ou não. Use `1` para quando for `null`, e `0` para quando não for.

Exemplo:

A url `api/users?is_null[cpf]=1` é equivalente à `User::whereNull('cpf')`

A url `api/users?is_null[age]=0` é equivalente à `User::whereNotNull('age')`



## `not_in`

Filtra quando multiplos valores não estão presente numa coluna. 

Exemplo:

A url `api/users?not_in[role][]=1&not_in[role][]=2` é equivalente à `User::whereNotIn('role', [1, 2])`

**Observação**: Quando o `not_in[my_field]` for equivalente a um array vazio, nada será executado.


## `in`

Filtra quando multiplos valores estão presentes numa coluna. 

Exemplo:

A url `api/users?in[role][]=10&in[role][]=20` é equivalente à `User::whereIn('role', [10, 20])`

**Observação**: Quando o `in[my_field]` for equivalente a um array vazio, nada será executado.

## `date_max`
Filtrar uma data através de um valor máximo.

A url `api/users?date_max[created_at]=2021-01-01` é equivalente a `User::whereDate('created_at', '<=', '2021-01-01')`

## `date_min`

Filtra um campo de data através de um valor mínimo.

Exemplo:

A url `api/users?date_min[created_at]=2021-01-01` é equivalente a `User::whereDate('created_at', '>=', '2021-01-01')`


## `not_equal`

Aplica um filtro utilizando o operador "não igual".

Exemplo:

A url `api/users?not_equal[profile_id]=3` é equivalente a `User::where('profile_id', '<>', '3')`


## Filtering relationship fields

É possível aplicar os filtros de pesquisa desta biblioteca nos relacionamentos definidos no seu Model.

Por exemplo:

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

Para quem utiliza `axios` para consumir uma API construida no Laravel, pode-se perfeitamente utilizar a opção `params` para incluir as buscas mostradas acima.

Exemplo:

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


## Restrição de campos

É possível configurar o filtro para ele aceitar apenas determinados campos. Isso permite especificar melhor os campos que podem ser filtrados.
Você só precisa passar um `array` contedo as seguintes especificações:

```php
[
    'name' => 'contains' // só aceita "contains" para o campo "name",
    'created_at' => ['date_min', 'date_max'] // aceita os dois filtros para o campo "created_at",
    'phones.number' => true, // Aceita qualquer filtro para o campo "number" do relacionamento "phones()"
    'profile_id'  => '*' // Aceita qualquer filtro para o campo profile_id
]
```

Você pode fazer isso de duas formas. 

### Restrição de campos filtrados no Model

No model, você apenas precisar definir a propriedade `$allowedFilters` com as restrições necessárias

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


### Usando o método allow

```php
$alloweds = [
    'name' => 'contains'
];

$query = User::query();

(new Filter)->allow($alloweds)->apply($query, $request)
```

### Usando Filter::fromModel

```php
$allowed = [
    'name' => 'contains'
];

$query = Filter::fromModel(User::class, $request, $allowed);

return $query->paginate();
```

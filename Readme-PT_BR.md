# Eloquent Filter

Uma simples biblioteca para facilitar a utilização de filtros no Eloquent. É muito útil para padronizar consultas em chamadas de API.


## Descrição

Essa biblioteca tem como finalidade facilitar e padronizar a utilização de filtros de pesquisa para o Laravel. A ideia é agregar vários filtros simplesmente passando os valores na sua requisição. Além do mais, essa biblioteca ajuda a evitar que você escrevar (ou reescreva) várias linhas de código para tratar filtros de pesquisa apliacados à sua consulta.

## Instalação

Rode o comando 

```composer require laravellegends/eloquent-filter```

## Exemplos de uso:

Existem duas maneiras de utilizar a biblioteca Eloquent Filter. 

### Utilizando o trait `HasFilter`

O trait `LaravelLegends\EloquentFilter\HasFilter` pode ser utilizado no model onde você deseja aplicar os filtros. Ao adicionar o `trait`, o método `filter` estará disponível.
 

#### Exemplo
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

### Utilizando a classe `Filter`

Você também pode utilizar a classe `Filter` diretamente para aplicar em suas consultas com eloquent.

Veja:

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
        $query = User::query();

        $filter = new Filter;
        $filter->apply($query, $request);
    
        // Check if `order_by` parameter is present
        if ($request->has('order_by')) {
            $orderBy = $request->input('order_by');
    
            if (is_array($orderBy)) {
                // Multi-ordering with array of columns and directions
                foreach ($orderBy as $order) {
                    $column = $order['column'];
                    $direction = $order['direction'] ?? 'asc';
    
                    $query->orderBy($column, $direction);
                }
            } else {
                // Single-ordering with a single column
                $query->orderBy($orderBy);
            }
        } else {
            $query->orderBy('name'); // Default ordering if `order_by` parameter is not provided
        }
    
        return $query->paginate();
    }
}
```

Note que, no segundo exemplo, precisamos passar uma instância de `Request`. Isso é muito útil em casos onde você queria utilizar as requests criadas por `make:request`.


## Como funciona?
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


## Filtrando campos de relacionamentos

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

## Exemplos com Axios

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

No model, você apenas precisar definir a propriedade `$filterRestrictions` com as restrições necessárias

```php
use LaravelLegends\EloquentFilter\HasFilter;

class User extends Model
{
    use HasFilter;

    protected $filterRestrictions = [
        'name'         => 'contains',
        'phone.number' => 'contains',
        'price'        => ['max', 'min'],
        'profile_id'   => '*',
    ];
}
```


### Usando o método restrict

```php
$restriction = [
    'name' => 'contains'
];

$query = User::query();

(new Filter)->restrict($restriction)->apply($query, $request)
```

### Usando Filter::fromModel

```php
$restriction = [
    'name' => 'contains'
];

$query = Filter::fromModel(User::class, $request, $restriction);

return $query->paginate();
```

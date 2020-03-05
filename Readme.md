# Eloquent Filter

Uma simples biblioteca para facilitar a utilização de filtros no Eloquent. É muito útil para padronizar consultas em chamadas de API.

## Instalação

Rode o comando 

```composer require laravellegends/eloquent-filter```

## Exemplos:

```php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelLegends\EloquentFilter\Filter;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = Filter::fromModel(User::class, $request);

        return $query->paginate();
    }
}
```


Faça a chamada `api/users?contains[name]=search+term`


Ao fazer isso, o `Filter` esperará a passagem de parâmetros específicos para realizar filtros padrão na sua consulta.

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

A url `api/users?is_null[cpf]=1` é equivalente à `User::whereNotNull('cpf')`

A url `api/users?is_null[age]=0` é equivalente à `User::whereNull('age')`



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
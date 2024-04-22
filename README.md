
# API desenvolvida para o [MapOS](https://github.com/RamonSilva20/mapos)

Para utilizar a API no [MapOS](https://github.com/RamonSilva20/mapos) baixe o .zip do projeto, descompacte na pasta principal do seu MapOS.
Vá ao arquivo `application/config/jwt.php`
e altere a variável `$config['jwt_key']`.
É necessário alterar o arquivo [`config.php`](https://github.com/RamonSilva20/mapos/blob/a6ae21e0e64aa2407005ab246d1c6efa445f68dc/application/config/config.php#L449) do mapos, alterando a variável `$config['csrf_exclude_uris']` alterando para 
```php
$config['csrf_exclude_uris'] = ["api.*+"];
```
Altere também o arquivo [`routes.php`](https://github.com/RamonSilva20/mapos/blob/a6ae21e0e64aa2407005ab246d1c6efa445f68dc/application/config/routes.php#L46) adicionado as rotas da API 
```php
// Rotas API
$route['api']                            = 'api/ApiController/index';
$route['api/audit']                      = 'api/ApiController/audit';
$route['api/login']                      = 'api/UsuariosController/login';
$route['api/reGenToken']                 = 'api/UsuariosController/reGenToken';
$route['api/conta']                      = 'api/UsuariosController/conta';
$route['api/emitente']                   = 'api/ApiController/emitente';
$route['api/clientes']                   = 'api/ClientesController/index';
$route['api/clientes/(:num)']            = 'api/ClientesController/index/$1';
$route['api/produtos']                   = 'api/ProdutosController/index';
$route['api/produtos/(:num)']            = 'api/ProdutosController/index/$1';
$route['api/servicos']                   = 'api/ServicosController/index';
$route['api/servicos/(:num)']            = 'api/ServicosController/index/$1';
$route['api/usuarios']                   = 'api/UsuariosController/index';
$route['api/usuarios/(:num)']            = 'api/UsuariosController/index/$1';
$route['api/os']                         = 'api/OsController/index';
$route['api/os/(:num)']                  = 'api/OsController/index/$1';
$route['api/os/(:num)/produtos']         = 'api/OsController/produtos/$1';
$route['api/os/(:num)/produtos/(:num)']  = 'api/OsController/produtos/$1/$2';
$route['api/os/(:num)/servicos']         = 'api/OsController/servicos/$1';
$route['api/os/(:num)/servicos/(:num)']  = 'api/OsController/servicos/$1/$2';
$route['api/os/(:num)/anotacoes']        = 'api/OsController/anotacoes/$1';
$route['api/os/(:num)/anotacoes/(:num)'] = 'api/OsController/anotacoes/$1/$2';
$route['api/os/(:num)/anexos']           = 'api/OsController/anexos/$1';
$route['api/os/(:num)/anexos/(:num)']    = 'api/OsController/anexos/$1/$2';
$route['api/os/(:num)/desconto']         = 'api/OsController/desconto/$1';
```

### Contribuidores
[![Contribuidores](https://contrib.rocks/image?repo=juliolobo/api-mapos)](https://github.com/juliolobo/api-mapos/graphs/contributors)

## Autor
| [<img src="https://avatars.githubusercontent.com/juliolobo?s=115"><br><sub>Julio Lobo</sub>](https://github.com/juliolobo) |
| :---: |

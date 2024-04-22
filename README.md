
# API desenvolvida para o [MapOS](https://github.com/RamonSilva20/mapos)

Esta versão está sendo disponibilizada para pessoas que possuem uma versão mais antiga do Map-OS que ainda não contam com a API.

Para utilizar a API no [MapOS](https://github.com/RamonSilva20/mapos) baixe o .zip do projeto, descompacte na pasta principal do seu MapOS.
Vá ao arquivo `application/config/jwt.php`
e altere a variável `$config['jwt_key']`.
É necessário alterar o arquivo [`config.php`](https://github.com/RamonSilva20/mapos/blob/05b37a181d0e3fd38e36c4d1238d638eb621b556/application/config/config.php#L449) do mapos, alterando a variável `$config['csrf_exclude_uris']` alterando para 
```php
$config['csrf_exclude_uris'] = ["api.*+"];
```
Altere também o arquivo [`routes.php`](https://github.com/RamonSilva20/mapos/blob/a6ae21e0e64aa2407005ab246d1c6efa445f68dc/application/config/routes.php#L46) Incluindo um `require`
```php
// Rotas da API
require(APPPATH.'config/routes_api.php');
```

### Contribuidores
[![Contribuidores](https://contrib.rocks/image?repo=juliolobo/api-mapos)](https://github.com/juliolobo/api-mapos/graphs/contributors)

## Autor
| [<img src="https://avatars.githubusercontent.com/juliolobo?s=115"><br><sub>Julio Lobo</sub>](https://github.com/juliolobo) |
| :---: |

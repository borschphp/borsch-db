# Borsch Db

Interact easily with your databases.

## Installation

Via [composer](https://getcomposer.org/) :

`composer require borschphp/db`

## Usage

```php
require_once __DIR__.'/vendor/autoload.php';

\Borsch\Db\Db::addConnection(new \Laminas\Db\Adapter\Adapter([
    'driver' => 'Pdo_Mysql',
    'database' => 'film',
    'username' => 'root',
    'password' => 'root',
    'hostname' => 'localhost',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'driver_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci'
    ]
]), 'film');

\Borsch\Db\Db::addConnection(new \Laminas\Db\Adapter\Adapter([
    'driver' => 'Pdo_Mysql',
    'database' => 'music',
    'username' => 'root',
    'password' => 'root',
    'hostname' => 'localhost',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'driver_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci'
    ]
]), 'music');

$albums = \Borsch\Db\Db::connection('music')::select('SELECT * FROM `album`');

foreach ($albums as $album) {
    // ...
}
```

## Notes

Internally, the package uses Laminas-Db, you can check [the documentation](https://docs.laminas.dev/laminas-db/adapter/) about how to create your adapters.

## License

The package is licensed under the MIT license. See [License File](https://github.com/borschphp/borsch-db/blob/master/LICENSE.md) for more information.
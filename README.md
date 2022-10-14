# Borsch Db

Interact easily with your SQL databases.

## Installation

Via [composer](https://getcomposer.org/) :

`composer require borschphp/db`

## Usage

```php
require_once __DIR__.'/vendor/autoload.php';

use Borsch\Db\Db;

$db = new Db('sqlite:');
$db->run('create table orders (
    id INTEGER constraint orders_pk primary key autoincrement,
    customer_id INTEGER not null,
    product_id  INTEGER not null,
    price       REAL    not null,
    date_add    TEXT    not null
)');

$db->transaction(function (Db $db) {
    $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (1, 1, 1, 19.99, "2022-09-10")');
    $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (2, 1, 2, 14.99, "2022-10-08")');
    $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (3, 2, 1, 19.99, "2022-10-10")');
});

$orders = $db->select('SELECT * FROM `orders`');
$orders = $db->select('SELECT * FROM `orders` WHERE `id_customer` = ?', [1]);

$db->update('UPDATE `orders` SET price = ? WHERE `id` = ?', [9.99, 1]);
$db->delete('DELETE FROM `orders` WHERE id = ?', [3])

$all_order_customer_id_1 = $db->from('orders')
    ->where('customer_id', '=', 1)
    ->get();

$average_price = $db->from('orders')
    ->avg('price');

$a_bit_more_complex = $db->from('orders')
    ->selectAliased('ord', ['customer_id', 'product_id'])
    ->whereAliased('ord', 'customer_id', '=', 1)
    ->whereAliased('ord', 'product_id', '=', 1)
    ->groupByAliased('ord', 'id')
    ->limit(15, 30);

// Some other queries
$db->from('orders')
    ->where('id', '>', 2)
    ->delete();

$db->from('orders')
    ->where('id', '>', 2)
    ->update([
        'customer_id' => 42,
        'price' => 999.99
    ]);

$db->from('orders')
    ->insert([
        ['customer_id' => 42, 'product_id' => 28, 'price' => 999.99, 'date_add' => date('Y-m-d')]
    ])
```

## License

The package is licensed under the MIT license. See [License File](https://github.com/borschphp/db/blob/master/LICENSE.md) for more information.
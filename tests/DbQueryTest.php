<?php
/**
 * @author debuss-a
 */

namespace BorschTest;

use Borsch\Db\Db;
use Borsch\Db\DbQuery;
use Borsch\Db\Exception\DbQueryException;
use PHPUnit\Framework\TestCase;
use stdClass;

class DbQueryTest extends TestCase
{

    protected Db $db;

    public function setUp(): void
    {
        $this->db = new Db('sqlite:');
        $this->db->run('create table orders (
            id INTEGER constraint orders_pk primary key autoincrement,
            customer_id INTEGER not null,
            product_id  INTEGER not null,
            price       REAL    not null,
            date_add    TEXT    not null
        )');

        $this->db->transaction(function (Db $db) {
            $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (1, 1, 1, 19.99, "2022-09-10")');
            $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (2, 1, 2, 14.99, "2022-10-08")');
            $db->insert('INSERT INTO orders (id, customer_id, product_id, price, date_add) VALUES (3, 2, 1, 19.99, "2022-10-10")');
        });
    }

    public function testToString()
    {
        $query = (string)$this->db->from('orders')->where('product_id', '=', 1);

        $this->assertIsString($query);
        // SELECT * FROM `orders` WHERE (`product_id` = :iwcnq)
        $this->assertMatchesRegularExpression(
            '/SELECT \* FROM `orders` WHERE \(`product_id` = :[a-z]+\)/',
            str_replace(PHP_EOL, ' ', $query)
        );
    }

    public function testType()
    {
        $query = new DbQuery($this->db);
        $query
            ->type('SELECT')
            ->from('orders');

        $this->assertSame('SELECT', substr($query, 0, 6));
    }

    public function testTypeLowerKeysIsWorking()
    {
        $query = new DbQuery($this->db);
        $query
            ->type('select')
            ->from('orders');

        $this->assertSame('SELECT', substr($query, 0, 6));
    }

    public function testTypeThrowsExceptionWhenUnknown()
    {
        $query = new DbQuery($this->db);
        $this->expectException(DbQueryException::class);
        $query->type('WRONG');
    }

    public function testSelectIsWildcardByDefault()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders');
        $this->assertStringStartsWith('SELECT *', $query);
    }

    public function testSelectWithOneColumn()
    {
        $query = new DbQuery($this->db);
        $query
            ->select('customer_id')
            ->from('orders');

        $this->assertStringStartsWith('SELECT `customer_id`', $query);
    }

    public function testSelectWithMultipleColumns()
    {
        $query = new DbQuery($this->db);
        $query
            ->select(['customer_id', 'product_id'])
            ->from('orders');

        $this->assertStringStartsWith('SELECT `customer_id`, `product_id`', $query);
    }

    public function testSelectAliasedWithOneColumnStringString()
    {
        $query = new DbQuery($this->db);
        $query
            ->selectAliased('ord', 'customer_id')
            ->from('orders', 'ord');

        $this->assertStringStartsWith('SELECT `ord`.`customer_id`', $query);
    }

    public function testSelectAliasedWithMultipleColumnsStringArray()
    {
        $query = new DbQuery($this->db);
        $query
            ->selectAliased('ord', ['customer_id', 'product_id'])
            ->from('orders', 'ord');

        $this->assertStringStartsWith('SELECT `ord`.`customer_id`, `ord`.`product_id`', $query);
    }

    public function testSelectAliasedWithMultipleColumnsArrayNull()
    {
        $query = new DbQuery($this->db);
        $query
            ->selectAliased([
                ['ord' => 'customer_id'],
                ['tst' => 'random_col']
            ])
            ->from('orders', 'ord');

        $this->assertStringStartsWith('SELECT `ord`.`customer_id`, `tst`.`random_col`', $query);
    }

    public function testFrom()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertStringStartsWith('SELECT * FROM `orders`', str_replace(PHP_EOL, ' ', $query));
    }

    public function testFromWithAlias()
    {
        $query = new DbQuery($this->db);
        $query->from('orders', 'o');

        $this->assertStringStartsWith('SELECT * FROM `orders` `o`', str_replace(PHP_EOL, ' ', $query));
    }

    public function testFromMultiple()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->from('customers');

        $this->assertStringStartsWith('SELECT * FROM `orders`, `customers`', str_replace(PHP_EOL, ' ', $query));
    }

    public function testLeftJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->leftJoin('customers', 'c', 'o.`customer_id` = c.`id`');

        $this->assertStringContainsString('LEFT JOIN `customers` `c`  ON o.`customer_id` = c.`id`', $query);
    }

    public function testInnerJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->innerJoin('customers', 'c', 'o.`customer_id` = c.`id`');

        $this->assertStringContainsString('INNER JOIN `customers` `c`  ON o.`customer_id` = c.`id`', $query);
    }

    public function testLeftOuterJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->leftOuterJoin('customers', 'c', 'o.`customer_id` = c.`id`');

        $this->assertStringContainsString('LEFT OUTER JOIN `customers` `c`  ON o.`customer_id` = c.`id`', $query);
    }

    public function testNaturalJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->naturalJoin('customers', 'c', 'o.`customer_id` = c.`id');

        $this->assertStringContainsString('NATURAL JOIN `customers` `c`', $query);
    }

    public function testRightJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->rightJoin('customers', 'c', 'o.`customer_id` = c.`id`');

        $this->assertStringContainsString('RIGHT JOIN `customers` `c`  ON o.`customer_id` = c.`id`', $query);
    }

    public function testWhere()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2);

        $this->assertMatchesRegularExpression(
            '/SELECT \* FROM `orders` WHERE \(`id` = :[a-z]+\)/',
            str_replace(PHP_EOL, ' ', $query)
        );
    }

    public function testWhereWrongOperandThrowsException()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->expectException(DbQueryException::class);
        $query->where('id', 'UNKNOWN', 2);
    }

    public function testWhereAliased()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'ord')
            ->whereAliased('ord', 'id', '=', 2);

        $this->assertMatchesRegularExpression(
            '/SELECT \* FROM `orders` `ord` WHERE \(`ord`.`id` = :[a-z]+\)/',
            str_replace(PHP_EOL, ' ', $query)
        );
    }

    public function testHaving()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2)
            ->having('price', '>=', 20);

        $this->assertMatchesRegularExpression(
            '/HAVING \(`price` >= :[a-z]+\)/',
            str_replace(PHP_EOL, ' ', $query)
        );
    }

    public function testHavingWrongOperandThrowsException()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2);

        $this->expectException(DbQueryException::class);
        $query->having('price', 'UNKNOWN', 20);
    }

    public function testHavingAliased()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'ord')
            ->where('id', '=', 2)
            ->havingAliased('ord', 'price', '>=', 20);

        $this->assertMatchesRegularExpression(
            '/HAVING \(`ord`.`price` >= :[a-z]+\)/',
            str_replace(PHP_EOL, ' ', $query)
        );
    }

    public function testOrderByAsc()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2)
            ->orderBy('price');

        $this->assertStringContainsString('ORDER BY `price` ASC', $query);
    }

    public function testOrderByDesc()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2)
            ->orderBy('price', 'DESC');

        $this->assertStringContainsString('ORDER BY `price` DESC', $query);
    }

    public function testOrderByWrongDirectionThrowsException()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2);

        $this->expectException(DbQueryException::class);
        $query->orderBy('price', 'UNKNOWN');
    }

    public function testOrderByAliased()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'ord')
            ->where('id', '=', 2)
            ->orderByAliased('ord', 'price');

        $this->assertStringContainsString('ORDER BY `ord`.`price` ASC', $query);
    }

    public function testGroupBy()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2)
            ->groupBy('customer_id');

        $this->assertStringContainsString('GROUP BY `customer_id`', $query);
    }

    public function testGroupByMultiple()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 2)
            ->groupBy('customer_id')
            ->groupBy('price');

        $this->assertStringContainsString('GROUP BY `customer_id`, `price`', $query);
    }

    public function testGroupByAliased()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'ord')
            ->where('id', '=', 2)
            ->groupByAliased('ord', 'customer_id');

        $this->assertStringContainsString('GROUP BY `ord`.`customer_id`', $query);
    }

    public function testLimit()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('customer_id', '=', 1)
            ->limit(1);

        $this->assertStringContainsString('LIMIT 1', $query);

        $result = $this->db->select($query);

        $this->assertCount(1, $this->db->select($query));
        $this->assertSame(1, $result[0]->product_id);
    }

    public function testLimitWithOffset()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('customer_id', '=', 1)
            ->limit(1, 1);

        $this->assertStringContainsString('LIMIT 1, 1', $query);

        $result = $this->db->select($query);

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]->product_id);
    }

    public function testGet()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('customer_id', '=', 1);

        $result = $query->get();
        $this->assertCount(2, $result);
        foreach ($result as $row) {
            $this->assertSame(1, $row->customer_id);
        }
    }

    public function testFirst()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('customer_id', '=', 1)
            ->orderBy('id');

        $result = $query->first();
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame(1, $result->product_id);
    }

    public function testValue()
    {
        $query = new DbQuery($this->db);
        $query
            ->select('price')
            ->from('orders')
            ->where('id', '=', 1);

        $price = $query->value();
        $this->assertIsFloat($price);
        $this->assertSame(19.99, $price);
    }

    public function testCount()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertSame(3, $query->count());
    }

    public function testMax()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertSame(3, $query->max('id'));
    }

    public function testMaxAliased()
    {
        $query = new DbQuery($this->db);
        $query->from('orders', 'ord');

        $this->assertSame(3, $query->max('id', 'ord'));
    }

    public function testMin()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertSame(1, $query->min('id'));
    }

    public function testMinAlias()
    {
        $query = new DbQuery($this->db);
        $query->from('orders', 'ord');

        $this->assertSame(1, $query->min('id', 'ord'));
    }

    public function testAvg()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertSame(2.0, $query->avg('id'));
    }

    public function testAvgAliased()
    {
        $query = new DbQuery($this->db);
        $query->from('orders', 'ord');

        $this->assertSame(2.0, $query->avg('id', 'ord'));
    }

    public function testSum()
    {
        $query = new DbQuery($this->db);
        $query->from('orders');

        $this->assertSame(6, $query->sum('id'));
    }

    public function testSumAliased()
    {
        $query = new DbQuery($this->db);
        $query->from('orders', 'ord');

        $this->assertSame(6, $query->sum('id', 'ord'));
    }

    public function testInsertOne()
    {
        $date = date('Y-m-d');

        $query = new DbQuery($this->db);
        $query->from('orders');

        $success = $query->insert(['customer_id' => 20, 'product_id' => 40, 'price' => 33.99, 'date_add' => $date]);

        $this->assertTrue($success);

        $query = new DbQuery($this->db);
        $query->from('orders')->where('customer_id', '=', 20);

        $result = $query->first();

        $this->assertSame(40, $result->product_id);
        $this->assertSame(33.99, $result->price);
        $this->assertSame($date, $result->date_add);
    }

    public function testInsertMultiple()
    {
        $date = date('Y-m-d');

        $query = new DbQuery($this->db);
        $query->from('orders');

        $success = $query->insert([
            ['customer_id' => 20, 'product_id' => 40, 'price' => 33.99, 'date_add' => $date],
            ['customer_id' => 21, 'product_id' => 41, 'price' => 34.99, 'date_add' => $date],
            ['customer_id' => 22, 'product_id' => 42, 'price' => 35.99, 'date_add' => $date]
        ]);

        $this->assertTrue($success);

        $query = new DbQuery($this->db);
        $query->from('orders')->where('customer_id', '=', 21);

        $result = $query->first();

        $this->assertSame(41, $result->product_id);
        $this->assertSame(34.99, $result->price);
        $this->assertSame($date, $result->date_add);
    }

    public function testUpdate()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 3)
            ->update(['customer_id' => 20, 'product_id' => 40, 'price' => 33.99]);

        $query = new DbQuery($this->db);
        $query->from('orders')->where('customer_id', '=', 20);

        $result = $query->first();

        $this->assertSame(40, $result->product_id);
        $this->assertSame(33.99, $result->price);
    }

    public function testDelete()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders')
            ->where('id', '=', 3)
            ->delete();

        $this->assertNull($query->first());
    }
}

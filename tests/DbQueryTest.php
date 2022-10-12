<?php
/**
 * @author debuss-a
 */

namespace BorschTest;

use Borsch\Db\Db;
use Borsch\Db\DbQuery;
use Borsch\Db\Exception\DbQueryException;
use PHPUnit\Framework\TestCase;

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
        $query = (string)$this->db->table('orders')->where('product_id', '=', 1);

        $this->assertIsString($query);
        // SELECT * FROM `orders` WHERE (`product_id` = :iwcnq)
        $this->assertMatchesRegularExpression(
            '/SELECT \* FROM \`orders\` WHERE \(\`product_id\` \= \:[a-z]+\)/',
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

    public function testSelectWithColumns()
    {
        $query = new DbQuery($this->db);
        $query
            ->select('customer_id', 'product_id')
            ->from('orders');

        $this->assertStringStartsWith('SELECT customer_id, product_id', $query);
    }

    public function testSelectEmptyKeepWildcard()
    {
        $query = new DbQuery($this->db);
        $query
            ->select()
            ->from('orders');

        $this->assertStringStartsWith('SELECT *', $query);
    }

    public function testAddSelect()
    {
        $query = new DbQuery($this->db);
        $query
            ->select('customer_id', 'product_id')
            ->from('orders');
        $query->addSelect('price');

        $this->assertStringStartsWith('SELECT customer_id, product_id, price', $query);
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
            ->leftJoin('customers', 'c', 'o.`customer_id` = c.`id');

        $this->assertStringContainsString('LEFT JOIN `customers` `c`  ON o.`customer_id` = c.`id', $query);
    }

    public function testInnerJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->innerJoin('customers', 'c', 'o.`customer_id` = c.`id');

        $this->assertStringContainsString('INNER JOIN `customers` `c`  ON o.`customer_id` = c.`id', $query);
    }

    public function testLeftOuterJoin()
    {
        $query = new DbQuery($this->db);
        $query
            ->from('orders', 'o')
            ->leftOuterJoin('customers', 'c', 'o.`customer_id` = c.`id');

        $this->assertStringContainsString('LEFT OUTER JOIN `customers` `c`  ON o.`customer_id` = c.`id', $query);
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
            ->rightJoin('customers', 'c', 'o.`customer_id` = c.`id');

        $this->assertStringContainsString('RIGHT JOIN `customers` `c`  ON o.`customer_id` = c.`id', $query);
    }

    public function testWhere()
    {

    }

    public function testHaving()
    {

    }

    public function testOrderBy()
    {

    }

    public function testGroupBy()
    {

    }

    public function testLimit()
    {

    }

    public function testGet()
    {

    }

    public function testFirst()
    {

    }

    public function testValue()
    {

    }

    public function testFind()
    {

    }

    public function testCount()
    {

    }

    public function testMax()
    {

    }

    public function testMin()
    {

    }

    public function testAvg()
    {

    }

    public function testSum()
    {

    }

    public function testInsert()
    {

    }

    public function testUpdate()
    {

    }

    public function testDelete()
    {

    }
}

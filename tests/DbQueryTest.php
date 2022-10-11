<?php
/**
 * @author debuss-a
 */

namespace BorschTest;

use Borsch\Db\Db;
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

    public function testInnerJoin()
    {

    }

    public function test__toString()
    {

    }

    public function testOrderBy()
    {

    }

    public function testFrom()
    {

    }

    public function testAvg()
    {

    }

    public function testMax()
    {

    }

    public function testDelete()
    {

    }

    public function testAddSelect()
    {

    }

    public function testFind()
    {

    }

    public function testUpdate()
    {

    }

    public function testInsert()
    {

    }

    public function testRightJoin()
    {

    }

    public function testValue()
    {

    }

    public function testGroupBy()
    {

    }

    public function testCount()
    {

    }

    public function testMin()
    {

    }

    public function testSum()
    {

    }

    public function testHaving()
    {

    }

    public function testSelect()
    {

    }

    public function testNaturalJoin()
    {

    }

    public function testType()
    {

    }

    public function testGet()
    {

    }

    public function testWhere()
    {

    }

    public function testLimit()
    {

    }

    public function testLeftOuterJoin()
    {

    }

    public function testLeftJoin()
    {

    }

    public function testFirst()
    {

    }
}

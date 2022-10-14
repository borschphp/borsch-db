<?php
/**
 * @author debuss-a
 */

namespace BorschTest;

use Borsch\Db\{Db, DbQuery};
use Error;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use stdClass;

class DbTest extends TestCase
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

    public function testGetPDO()
    {
        $this->assertInstanceOf(PDO::class, $this->db->getPDO());
    }

    public function testRunReturnsPdoStatementWhenNoArgsProvided()
    {
        $stmt = $this->db->run('SELECT * FROM orders');
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testRunReturnsPdoStatementWhenArgsProvided()
    {
        $stmt = $this->db->run('SELECT * FROM orders WHERE id = ?', [2]);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testSelectReturnsArrayOfObjectIfMoreThanOneResult()
    {
        $orders = $this->db->select('SELECT * FROM orders');
        $this->assertIsArray($orders);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $orders);
    }

    public function testSelectReturnsArrayOfObjectEvenIfOnlyOneResult()
    {
        $orders = $this->db->select('SELECT * FROM orders WHERE `id` = 1');
        $this->assertIsArray($orders);
        $this->assertCount(1, $orders);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $orders);
    }

    public function testSelectMakeUseOfArgs()
    {
        $orders = $this->db->select('SELECT * FROM orders WHERE `id` = ?', [2]);
        $this->assertIsArray($orders);
        $this->assertCount(1, $orders);
        $this->assertContainsOnlyInstancesOf(stdClass::class, $orders);
        $this->assertSame(2, $orders[0]->id);
    }

    public function testInsert()
    {
        $date = date('Y-m-d');
        $result = $this->db->insert(
            'INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)',
            [3, 3, 49.99, $date]
        );

        $this->assertTrue($result);
        $this->assertSame(
            $date,
            $this->db->run('SELECT `date_add` FROM `orders` WHERE `customer_id` = ? AND `price` = 49.99', [3])->fetchColumn()
        );
    }

    public function testGetLastInsertId()
    {
        $date = date('Y-m-d');
        $this->db->insert(
            'INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)',
            [3, 3, 49.99, $date]
        );

        $this->assertSame(4, (int)$this->db->getLastInsertId());
    }

    public function testUpdate()
    {
        $result = $this->db->update('UPDATE `orders` SET `customer_id` = ? WHERE `id` = ?', [42, 3]);

        $this->assertIsInt($result);
        $this->assertSame(
            42,
            $this->db->run('SELECT `customer_id` FROM `orders` WHERE `id` = ?', [3])->fetchColumn()
        );
    }

    public function testDelete()
    {
        $result = $this->db->delete('DELETE FROM `orders` WHERE `customer_id` = ?', [1]);

        $this->assertSame(2, $result);
        $this->assertSame(1, $this->db->run('SELECT COUNT(*) FROM `orders`')->fetchColumn());
    }

    public function testBeginTransaction()
    {
        $samples = [
            [3, 3, 2.99, '2020-04-08'],
            [5, 34, 489.99, '2020-07-02'],
            [7, 9, 4.99, '2021-07-14'],
            [2, 22, 79.99, '2022-09-23'],
            [54, 4, 9.99, '2022-12-25']
        ];

        $stmt = $this
            ->db
            ->getPDO()
            ->prepare('INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)');

        $result = $this->db->beginTransaction();
        try {
            foreach ($samples as $sample) {
                $stmt->execute($sample);
            }
            $this->db->getPDO()->commit();
        } catch (Exception) {
            $this->db->getPDO()->rollBack();
        }

        $this->assertTrue($result);
        foreach ($samples as $sample) {
            $this->assertSame(
                $sample[0],
                $this->db->run(
                    'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                    array_slice($sample, 1)
                )->fetchColumn()
            );
        }
    }

    public function testRollBack()
    {
        $date = date('Y-m-d');
        $this->db->beginTransaction();
        $this->db->insert(
            'INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)',
            [3, 3, 49.99, $date]
        );
        $result = $this->db->rollBack();

        $this->assertTrue($result);
        $this->assertSame(0, $this->db->run(
            'SELECT COUNT(*)
            FROM `orders`
            WHERE `customer_id` = ?
            AND `product_id` = ?
            AND `price` = ?
            AND `date_add` = ?',
            [3, 3, 49.99, $date]
        )->fetchColumn());
    }

    public function testCommit()
    {
        $date = date('Y-m-d');
        $this->db->beginTransaction();
        $this->db->insert(
            'INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)',
            [3, 3, 49.99, $date]
        );
        $result = $this->db->commit();

        $this->assertTrue($result);
        $this->assertSame(1, $this->db->run(
            'SELECT COUNT(*)
            FROM `orders`
            WHERE `customer_id` = ?
            AND `product_id` = ?
            AND `price` = ?
            AND `date_add` = ?',
            [3, 3, 49.99, $date]
        )->fetchColumn());
    }

    public function testTransaction()
    {
        $samples = [
            [3, 3, 2.99, '2020-04-08'],
            [5, 34, 489.99, '2020-07-02'],
            [7, 9, 4.99, '2021-07-14'],
            [2, 22, 79.99, '2022-09-23'],
            [54, 4, 9.99, '2022-12-25']
        ];

        $this->db->transaction(function ($db) use ($samples) {
            $stmt = $db
                ->getPDO()
                ->prepare('INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)');

            foreach ($samples as $sample) {
                $stmt->execute($sample);
            }
        });

        foreach ($samples as $sample) {
            $this->assertSame(
                $sample[0],
                $this->db->run(
                    'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                    array_slice($sample, 1)
                )->fetchColumn()
            );
        }
    }

    public function testTransactionIsRollbackedInCaseOfError()
    {
        $samples = [
            [3, 3, 2.99, '2020-04-08'],
            [5, 34, 489.99, '2020-07-02'],
            [7, new stdClass(), 4.99, '2021-07-14'],
            [2, 22, 79.99, '2022-09-23'],
            [54, 4, 9.99, '2022-12-25']
        ];

        $stmt = $this
            ->db
            ->getPDO()
            ->prepare('INSERT INTO `orders` (`customer_id`, `product_id`, `price`, `date_add`) VALUES (?, ?, ?, ?)');

        $exception_thrown = false;
        try {
            $this->db->transaction(function () use ($samples, $stmt): void {
                foreach ($samples as $sample) {
                    $stmt->execute($sample);
                }
            });
        } catch (Error) {
            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);

        // Samples 0 and 1 were created before error
        $this->assertSame(
            $samples[0][0],
            $this->db->run(
                'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                array_slice($samples[0], 1)
            )->fetchColumn()
        );
        $this->assertSame(
            $samples[1][0],
            $this->db->run(
                'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                array_slice($samples[1], 1)
            )->fetchColumn()
        );

        // Sample 2 causes error
        // Samples 3 and 4 are not created
        $this->assertFalse(
            $this->db->run(
                'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                array_slice($samples[3], 1)
            )->fetchColumn()
        );
        $this->assertFalse(
            $this->db->run(
                'SELECT `customer_id` FROM `orders` WHERE `product_id` = ? AND `price` = ? AND `date_add` = ?',
                array_slice($samples[4], 1)
            )->fetchColumn()
        );
    }

    public function testTable()
    {
        $this->assertInstanceOf(DbQuery::class, $this->db->from('orders'));
    }
}

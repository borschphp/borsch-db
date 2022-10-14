<?php
/**
 * @author debuss-a
 */

namespace Borsch\Db;

use Borsch\Db\Exception\DbQueryException;
use Closure;
use Exception;
use PDO;
use PDOStatement;

/**
 * Class Db
 * @package Borsch\Db
 */
class Db
{

    protected PDO $pdo;

    /**
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array $options
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, array $options = [])
    {
        $default_options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $options = array_replace($default_options, $options);

        $this->pdo = new PDO($dsn, $username, $password, $options);
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * @param string|DbQuery $query
     * @param array|null $args
     * @return PDOStatement
     * @throws DbQueryException
     */
    public function run(string|DbQuery $query, ?array $args = null): PDOStatement
    {
        if ($query instanceof DbQuery) {
            // $query->build first in order to fill bindings
            $sql = $query->build();

            $args = $query->getBindings();
            $query = $sql;
        }

        if (!$args || !count($args)) {
            return $this->pdo->query($query);
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);

        return $stmt;
    }

    /**
     * @param string|DbQuery $query
     * @param array|null $args
     * @return array
     * @throws DbQueryException
     */
    public function select(string|DbQuery $query, ?array $args = null): array
    {
        return $this->run($query, $args)->fetchAll();
    }

    /**
     * @param string|DbQuery $query
     * @param array|null $args
     * @return bool
     * @throws DbQueryException
     */
    public function insert(string|DbQuery $query, ?array $args = null): bool
    {
        return $this->run($query, $args)->rowCount() > 0;
    }

    /**
     * @param string|null $name
     * @return false|string
     */
    public function getLastInsertId(?string $name = null): false|string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @param string|DbQuery $query
     * @param array|null $args
     * @return int Affected rows
     * @throws DbQueryException
     */
    public function update(string|DbQuery $query, ?array $args = null): int
    {
        return $this->run($query, $args)->rowCount();
    }

    /**
     * @param string|DbQuery $query
     * @param array|null $args
     * @return int Affected rows
     * @throws DbQueryException
     */
    public function delete(string|DbQuery $query, ?array $args = null): int
    {
        return $this->run($query, $args)->rowCount();
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * @param callable $callback
     * @throws Exception
     */
    public function transaction(callable $callback): void
    {
        $this->beginTransaction();

        try {
            call_user_func($callback, $this);
            $this->commit();
        } catch (Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    /**
     * @param string $name
     * @return DbQuery
     */
    public function from(string $name): DbQuery
    {
        return (new DbQuery($this))->from($name);
    }
}

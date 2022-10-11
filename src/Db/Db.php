<?php
/**
 * @author debuss-a
 */

namespace Borsch\Db;

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
     * @param string $query
     * @param array|null $args
     * @return PDOStatement
     */
    public function run(string $query, ?array $args = null): PDOStatement
    {
        if (!$args || !count($args)) {
            return $this->pdo->query($query);
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);

        return $stmt;
    }

    /**
     * @param string $query
     * @param array|null $args
     * @return array
     */
    public function select(string $query, ?array $args = null): array
    {
        return $this->run($query, $args)->fetchAll();
    }

    /**
     * @param string $query
     * @param array|null $args
     * @return bool
     */
    public function insert(string $query, ?array $args = null): bool
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
     * @param string $query
     * @param array|null $args
     * @return int Affected rows
     */
    public function update(string $query, ?array $args = null): int
    {
        return $this->run($query, $args)->rowCount();
    }

    /**
     * @param string $query
     * @param array|null $args
     * @return int Affected rows
     */
    public function delete(string $query, ?array $args = null): int
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
     * @param Closure $closure
     * @throws Exception
     */
    public function transaction(Closure $closure): void
    {
        $this->beginTransaction();

        try {
            call_user_func($closure, $this);
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
    public function table(string $name): DbQuery
    {
        $builder = new DbQuery($this);
        $builder->from($name);

        return $builder;
    }
}

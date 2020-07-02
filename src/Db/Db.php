<?php
/**
 * @author debuss-a
 */

namespace Borsch\Db;

use Closure;
use InvalidArgumentException;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Throwable;

/**
 * Class Db
 * @package Borsch\Db
 */
class Db
{

    /** @var Adapter[] */
    protected static $connections = [];

    /** @var string */
    protected static $current;

    /**
     * @param Adapter $adapter
     * @param string $name
     */
    public static function addConnection(Adapter $adapter, string $name): void
    {
        self::$connections[$name] = $adapter;

        if (!self::$current) {
            self::$current = $name;
        }
    }

    /**
     * @param string $name
     * @return Adapter
     */
    public static function getAdapter(?string $name = null): Adapter
    {
        if (!$name) {
            $name = self::$current;
        }

        if (!isset(self::$connections[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid connection name "%s" (not found)...',
                $name
            ));
        }

        return self::$connections[$name];
    }

    /**
     * @param string $name
     * @return self
     */
    public static function connection(string $name): string
    {
        self::$current = $name;

        /** @var Db $instance */
        $instance = __CLASS__;

        return $instance;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return ResultSet|null
     */
    public static function select(string $query, array $parameters = []): ?ResultSet
    {
        $statement = self::getAdapter()->getDriver()->createStatement($query);
        $statement->prepare();

        $result = $statement->execute($parameters);

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $result_set = new ResultSet();
            $result_set->initialize($result);

            return $result_set;
        }

        return null;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    public static function insert(string $query, array $parameters = []): bool
    {
        $statement = self::getAdapter()->getDriver()->createStatement($query);
        $statement->prepare();

        $result = $statement->execute($parameters);

        if ($result instanceof ResultInterface && $result->getAffectedRows()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    public static function update(string $query, array $parameters = []): int
    {
        $statement = self::getAdapter()->getDriver()->createStatement($query);
        $statement->prepare();

        $result = $statement->execute($parameters);

        if ($result instanceof ResultInterface) {
            return $result->getAffectedRows();
        }

        return 0;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return int
     */
    public static function delete(string $query, array $parameters = []): int
    {
        return self::update($query, $parameters);
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    public static function execute(string $query, array $parameters = []): bool
    {
        $statement = self::getAdapter()->getDriver()->createStatement($query);
        $statement->prepare();

        $result = $statement->execute($parameters);

        if ($result instanceof ResultInterface) {
            return (bool)$result->getAffectedRows();
        }

        return false;
    }

    public static function beginTransaction(): void
    {
        self::getAdapter()->getDriver()->getConnection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getAdapter()->getDriver()->getConnection()->commit();
    }

    public static function rollBack(): void
    {
        self::getAdapter()->getDriver()->getConnection()->rollback();
    }

    /**
     * @param Closure $callable
     * @return bool
     */
    public static function transaction(Closure $callable): bool
    {
        self::beginTransaction();

        try {
            $callable();

            self::commit();
        } catch (Throwable $throwable) {
            self::rollback();

            return false;
        }

        return true;
    }
}

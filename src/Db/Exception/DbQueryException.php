<?php
/**
 * @author debuss-a
 */

namespace Borsch\Db\Exception;

use Exception;

/**
 * Class DbQueryException
 * @package Borsch\Db
 */
class DbQueryException extends Exception
{

    /**
     * @param string $type
     * @param array $expected
     * @return static
     */
    public static function wrongType(string $type, array $expected): static
    {
        return new static(sprintf(
            'Unknown type "%s", expected one of: %s.',
            $type,
            implode(', ', $expected)
        ));
    }

    /**
     * @return static
     */
    public static function missingTableName(): static
    {
        return new static('Table name missing in DbQuery object, cannot build the SQL query.');
    }

    /**
     * @param string $operand
     * @param array $expected
     * @return static
     */
    public static function unknownOperand(string $operand, array $expected): static
    {
        return new static(sprintf(
            'Unknown operand "%s", expected one of: %s.',
            $operand,
            implode(', ', $expected)
        ));
    }

    /**
     * @param string $direction
     * @param array $expected
     * @return static
     */
    public static function unknownDirection(string $direction, array $expected): static
    {
        return new static(sprintf(
            'Unknown direction "%s", expected one of: %s.',
            $direction,
            implode(', ', $expected)
        ));
    }

    public static function wrongSelects()
    {
        $right_format = [
            '[string => string]',
            '[string => string[]]',
            '[string[] => null]',
        ];

        return new static('Wrong select(s) provided, must be one of: '.implode(', ', $right_format));
    }
}

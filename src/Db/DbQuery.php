<?php
/**
 * @author debuss-a
 */

namespace Borsch\Db;

use Borsch\Db\Exception\DbQueryException;

/**
 * Class DbQuery
 * @package Borsch\Db
 */
class DbQuery
{

    protected string $type = 'SELECT';
    protected array $select = [];
    protected array $insert = [];
    protected array $update = [];
    protected array $from = [];
    protected array $join = [];
    protected array $where = [];
    protected array $group = [];
    protected array $having = [];
    protected array $order = [];
    protected array $limit = ['offset' => 0, 'limit' => 0];
    protected array $bindings = [];

    /**
     * @param Db $db
     */
    public function __construct(
        protected Db $db
    ) {}

    /**
     * @return string
     * @throws DbQueryException
     */
    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * @param string $field
     * @return string
     */
    protected function escapeIdentifier(string $field): string
    {
        return '`'.str_replace('`', '``', $field).'`';
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @param string $type
     * @return DbQuery
     * @throws DbQueryException
     */
    public function type(string $type): DbQuery
    {
        $types = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];

        $type = strtoupper($type);
        if (!in_array($type, $types)) {
            throw DbQueryException::wrongType($type, $types);
        }

        $this->type = $type;
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     * @throws DbQueryException
     */
    public function select(string|array $columns): DbQuery
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        return $this->selectAliased(array_map(fn ($column) => [$column], $columns));
    }

    /**
     * @param string|array $alias
     * @param string|array|null $columns
     * @return $this
     * @throws DbQueryException
     */
    public function selectAliased(string|array $alias, string|array|null $columns = null): DbQuery
    {
        if (is_string($alias) && is_string($columns)) {
            $alias = [[$alias => $columns]];
        } elseif (is_string($alias) && is_array($columns)) {
            $alias = array_map(function ($column) use ($alias) {
                return [$alias => $column];
            }, $columns);
        } elseif (!is_array($alias) && !is_null($columns)) {
            throw DbQueryException::wrongSelects();
        }

        $this->select = array_reduce($alias, function ($acc, $cur) {
            foreach ($cur as $a => $c) {
                $acc[] = sprintf(
                    '%s%s',
                    is_int($a) ? '' : ($this->escapeIdentifier($a).'.'),
                    $this->escapeIdentifier($c)
                );
            }

            return $acc;
        }, []);

        return $this;
    }

    /**
     * @param string $columns
     * @return $this
     */
    protected function selectRaw(string $columns): DbQuery
    {
        $this->select = [$columns];

        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return $this
     */
    public function from(string $table, ?string $alias = null): DbQuery
    {
        if (strlen($table)) {
            $this->from[] = trim(sprintf(
                '%s %s',
                $this->escapeIdentifier($table),
                $alias ? $this->escapeIdentifier($alias) : ''
            ));
        }

        return $this;
    }

    /**
     * @param string $join
     * @return $this
     */
    protected function join(string $join): DbQuery
    {
        if (strlen($join)) {
            $this->join[] = $join;
        }

        return $this;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function leftJoin(string $table, ?string $alias = null, ?string $on = null): DbQuery
    {
        return $this->join(sprintf(
            'LEFT JOIN %s %s %s',
            $this->escapeIdentifier($table),
            $alias ? $this->escapeIdentifier($alias) : '',
            $on ? (' ON '.$on) : ''
        ));
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function innerJoin(string $table, string $alias = null, ?string $on = null): DbQuery
    {
        return $this->join(sprintf(
            'INNER JOIN %s %s %s',
            $this->escapeIdentifier($table),
            $alias ? $this->escapeIdentifier($alias) : '',
            $on ? (' ON '.$on) : ''
        ));
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function leftOuterJoin(string $table, string $alias = null, ?string $on = null): DbQuery
    {
        return $this->join(sprintf(
            'LEFT OUTER JOIN %s %s %s',
            $this->escapeIdentifier($table),
            $alias ? $this->escapeIdentifier($alias) : '',
            $on ? (' ON '.$on) : ''
        ));
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return $this
     */
    public function naturalJoin(string $table, string $alias = null): DbQuery
    {
        return $this->join(sprintf(
            'NATURAL JOIN %s %s',
            $this->escapeIdentifier($table),
            $alias ? $this->escapeIdentifier($alias) : ''
        ));
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @param string|null $on
     * @return $this
     */
    public function rightJoin(string $table, string $alias = null, ?string $on = null): DbQuery
    {
        return $this->join(sprintf(
            'RIGHT JOIN %s %s %s',
            $this->escapeIdentifier($table),
            $alias ? $this->escapeIdentifier($alias) : '',
            $on ? (' ON '.$on) : ''
        ));
    }

    /**
     * @param string $column
     * @param string $operand
     * @param string $value
     * @return $this
     * @throws DbQueryException
     */
    public function where(string $column, string $operand, string $value): DbQuery
    {
        return $this->whereAliased('', $column, $operand, $value);
    }

    /**
     * @param string $alias
     * @param string $column
     * @param string $operand
     * @param string $value
     * @return $this
     * @throws DbQueryException
     */
    public function whereAliased(string $alias, string $column, string $operand, string $value): DbQuery
    {
        $operand = strtoupper($operand);
        $operands = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE'];

        if (!in_array($operand, $operands)) {
            throw DbQueryException::unknownOperand($operand, $operands);
        }

        $key = $this->generateRandomBindingName();
        $restriction = sprintf(
            '%s%s %s :%s',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column),
            $operand,
            $key
        );

        $this->bindings[$key] = $value;
        $this->where[] = $restriction;

        return $this;
    }

    /**
     * @param string $column
     * @param string $operand
     * @param string $value
     * @return $this
     * @throws DbQueryException
     */
    public function having(string $column, string $operand, string $value): DbQuery
    {
        return $this->havingAliased('', $column, $operand, $value);
    }

    /**
     * @param string $alias
     * @param string $column
     * @param string $operand
     * @param string $value
     * @return $this
     * @throws DbQueryException
     */
    public function havingAliased(string $alias, string $column, string $operand, string $value): DbQuery
    {
        $operand = strtoupper($operand);
        $operands = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE'];

        if (!in_array($operand, $operands)) {
            throw DbQueryException::unknownOperand($operand, $operands);
        }

        $key = $this->generateRandomBindingName();
        $restriction = sprintf(
            '%s%s %s :%s',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column),
            $operand,
            $key
        );

        $this->bindings[$key] = $value;
        $this->having[] = $restriction;

        return $this;
    }

    /**
     * @param string $column
     * @param string $direction
     * @return $this
     * @throws DbQueryException
     */
    public function orderBy(string $column, string $direction = 'ASC'): DbQuery
    {
        return $this->orderByAliased('', $column, $direction);
    }

    /**
     * @param string $alias
     * @param string $column
     * @param string $direction
     * @return $this
     * @throws DbQueryException
     */
    public function orderByAliased(string $alias, string $column, string $direction = 'ASC'): DbQuery
    {
        $directions = ['ASC', 'DESC'];
        $direction = strtoupper($direction);

        if (!in_array($direction, $directions)) {
            throw DbQueryException::unknownDirection($direction, $directions);
        }

        $this->order[] = sprintf(
            '%s%s %s',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column),
            $direction
        );

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function groupBy(string $column): DbQuery
    {
        return $this->groupByAliased('', $column);
    }

    /**
     * @param string $alias
     * @param string $column
     * @return $this
     */
    public function groupByAliased(string $alias, string $column): DbQuery
    {
        $this->group[] = sprintf(
            '%s%s',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column)
        );

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0): DbQuery
    {
        $this->limit = [
            'offset' => max(0, $offset),
            'limit' => $limit,
        ];

        return $this;
    }

    /**
     * @return string
     * @throws DbQueryException
     */
    public function build(): string
    {
        if (!count($this->from)) {
            throw DbQueryException::missingTableName();
        }

        return match ($this->type) {
            'SELECT' => $this->buildSelect(),
            'INSERT' => $this->buildInsert(),
            'UPDATE' => $this->buildUpdate(),
            'DELETE' => $this->buildDelete()
        };
    }

    /**
     * @return string
     */
    protected function buildSelect(): string
    {
        $sql = 'SELECT '.(count($this->select) ? implode(', ', $this->select) : '*').PHP_EOL;
        $sql .= 'FROM '.implode(', ', $this->from).PHP_EOL;

        if (count($this->join)) {
            $sql .= implode(PHP_EOL, $this->join).PHP_EOL;
        }

        if ($this->where) {
            $sql .= 'WHERE ('.implode(') AND (', $this->where).')'.PHP_EOL;
        }

        if ($this->group) {
            $sql .= 'GROUP BY '.implode(', ', $this->group).PHP_EOL;
        }

        if ($this->having) {
            $sql .= 'HAVING ('.implode(') AND (', $this->having).')'.PHP_EOL;
        }

        if ($this->order) {
            $sql .= 'ORDER BY '.implode(', ', $this->order).PHP_EOL;
        }

        if ($this->limit['limit']) {
            $sql .= 'LIMIT '.($this->limit['offset'] ? $this->limit['offset'].', ' : '').$this->limit['limit'];
        }

        return trim($sql);
    }

    /**
     * @return string
     */
    protected function buildInsert(): string
    {
        $sql = $this->type.' ';
        $sql .= 'INTO '.reset($this->from).PHP_EOL;

        $sql .= sprintf(
            '(%s)',
            implode(', ', array_map([$this, 'escapeIdentifier'], array_keys(reset($this->insert))))
        ).PHP_EOL;

        $sql .= 'VALUES '.PHP_EOL;
        foreach ($this->insert as $row) {
            $sql .= sprintf(
                '(%s), ',
                implode(', ', array_pad([], count($row), '?'))
            );

            $this->bindings = array_merge($this->bindings, array_values($row));
        }

        $sql = trim($sql, ', ');

        return $sql;
    }

    /**
     * @return string
     */
    protected function buildUpdate():string
    {
        $sql = $this->type.' ';
        $sql .= reset($this->from).PHP_EOL;

        $sql .= 'SET '.PHP_EOL;
        foreach ($this->update as $key => $value) {
            $sql .= sprintf('%s = ?, ', $this->escapeIdentifier($key));
            $this->bindings[] = $value;
        }

        $sql = trim($sql, ', ').PHP_EOL;

        if ($this->where) {
            $sql .= 'WHERE ('.implode(') AND (', $this->where).')'.PHP_EOL;
        }

        return trim($sql);
    }

    /**
     * @return string
     */
    protected function buildDelete(): string
    {
        $sql = $this->type.' ';
        $sql .= 'FROM '.implode(', ', $this->from).PHP_EOL;

        if ($this->where) {
            $sql .= 'WHERE ('.implode(') AND (', $this->where).')'.PHP_EOL;
        }

        if ($this->limit['limit']) {
            $sql .= 'LIMIT '.($this->limit['offset'] ? $this->limit['offset'].', ' : '').$this->limit['limit'];
        }

        return trim($sql);
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->db->select($this);
    }

    /**
     * @return mixed
     */
    public function first(): mixed
    {
        return $this->db->select($this)[0] ?? null;
    }

    /**
     * @return mixed
     */
    public function value(): mixed
    {
        $this->limit(1);

        return $this->db->run($this)->fetchColumn();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $this->selectRaw('count(*) AS aggregate');

        return (int)$this->value();
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return mixed
     */
    public function max(string $column, ?string $alias = null): mixed
    {
        $this->selectRaw(sprintf(
            'MAX(%s%s) as aggregate',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column)
        ));

        return $this->value();
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return mixed
     */
    public function min(string $column, ?string $alias = null): mixed
    {
        $this->selectRaw(sprintf(
            'MIN(%s%s) as aggregate',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column)
        ));

        return $this->value();
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return mixed
     */
    public function avg(string $column, ?string $alias = null): mixed
    {
        $this->selectRaw(sprintf(
            'AVG(%s%s) as aggregate',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column)
        ));

        return $this->value();
    }

    /**
     * @param string $column
     * @param string|null $alias
     * @return mixed
     */
    public function sum(string $column, ?string $alias = null): mixed
    {
        $this->selectRaw(sprintf(
            'SUM(%s%s) as aggregate',
            strlen($alias) ? ($this->escapeIdentifier($alias).'.') : '',
            $this->escapeIdentifier($column)
        ));

        return $this->value();
    }

    /**
     * @param array $data
     * @return bool
     * @throws DbQueryException
     */
    public function insert(array $data): bool
    {
        if (!is_array(reset($data))) {
            $data = [$data];
        }

        $this->type('INSERT');
        $this->insert = $data;

        return $this->db->insert($this->build(), $this->bindings);
    }

    /**
     * @param array $data
     * @return int
     * @throws DbQueryException
     */
    public function update(array $data): int
    {
        $this->type('UPDATE');
        $this->update = $data;

        return $this->db->update($this->build(), $this->bindings);
    }

    /**
     * @return int
     * @throws DbQueryException
     */
    public function delete(): int
    {
        $this->type('DELETE');

        return $this->db->delete($this->build(), $this->bindings);
    }

    /**
     * @return string
     */
    private function generateRandomBindingName(): string
    {
        return substr(str_shuffle(implode(range('a','z'))), 0, 5);
    }
}

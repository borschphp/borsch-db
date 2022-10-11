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
     * @param string ...$columns
     * @return $this
     */
    public function select(string ...$columns): DbQuery
    {
        if (count($columns)) {
            $this->select = $columns;
        }

        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function addSelect(string ...$columns): DbQuery
    {
        if (count($columns)) {
            $this->select = array_merge($this->select, $columns);
        }

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
        $operand = strtoupper($operand);
        $operands = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE'];

        if (!in_array($operand, $operands)) {
            throw DbQueryException::unknownOperand($operand, $operands);
        }

        $key = $this->generateRandomBindingName();
        $restriction = sprintf(
            '%s %s :%s',
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
        $operand = strtoupper($operand);
        $operands = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE'];

        if (!in_array($operand, $operands)) {
            throw DbQueryException::unknownOperand($operand, $operands);
        }

        $key = $this->generateRandomBindingName();
        $restriction = sprintf(
            '%s %s :%s',
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
        $directions = ['ASC', 'DESC'];
        $direction = strtoupper($direction);

        if (!in_array($direction, $directions)) {
            throw DbQueryException::unknownDirection($direction, $directions);
        }

        $this->order[] = sprintf(
            ' %s %s',
            $this->escapeIdentifier($column),
            $direction
        );

        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function groupBy(string ...$columns): DbQuery
    {
        if (count($columns)) {
            $this->group = $columns;
        }

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
            'limit'  => $limit,
        ];

        return $this;
    }

    /**
     * @return string
     * @throws DbQueryException
     */
    protected function build(): string
    {
        if ($this->type == 'SELECT') {
            $sql = 'SELECT '.(count($this->select) ? implode(', ', $this->select) : '*').PHP_EOL;
        } else {
            $sql = $this->type.' ';
        }

        if (!count($this->from)) {
            throw DbQueryException::missingTableName();
        }

        if (in_array($this->type, ['SELECT', 'DELETE'])) {
            $sql .= 'FROM '.implode(', ', $this->from).PHP_EOL;
        } elseif ($this->type == 'INSERT') {
            $sql .= 'INTO '.reset($this->from).PHP_EOL;
        } elseif ($this->type == 'UPDATE') {
            $sql .= reset($this->from).PHP_EOL;
        }

        if ($this->type == 'SELECT' && count($this->join)) {
            $sql .= implode(PHP_EOL, $this->join).PHP_EOL;
        }

        if ($this->type == 'UPDATE') {
            $sql .= 'SET '.PHP_EOL;
            foreach ($this->update as $key => $value) {
                $sql .= sprintf('%s = ?, ', $this->escapeIdentifier($key));
                $this->bindings[] = $value;
            }

            $sql = trim($sql, ', ').PHP_EOL;
        }

        if (in_array($this->type, ['SELECT', 'UPDATE', 'DELETE']) && $this->where) {
            $sql .= 'WHERE ('.implode(') AND (', $this->where).')'.PHP_EOL;
        }

        if ($this->type == 'SELECT' && $this->group) {
            $sql .= 'GROUP BY '.implode(', ', $this->group).PHP_EOL;
        }

        if ($this->type == 'SELECT' && $this->having) {
            $sql .= 'HAVING ('.implode(') AND (', $this->having).')'.PHP_EOL;
        }

        if ($this->type == 'SELECT' && $this->order) {
            $sql .= 'ORDER BY '.implode(', ', $this->order).PHP_EOL;
        }

        if (in_array($this->type, ['SELECT', 'DELETE']) && $this->limit['limit']) {
            $limit = $this->limit;
            $sql .= 'LIMIT '.($limit['offset'] ? $limit['offset'].', ' : '').$limit['limit'];
        }

        if ($this->type == 'INSERT') {
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
        }

        return trim($sql);
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->db->select($this, $this->bindings);
    }

    /**
     * @return mixed
     */
    public function first(): mixed
    {
        return $this->db->select($this, $this->bindings)[0] ?? null;
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        return $this->db->select($this, $this->bindings)[0]->{$column} ?? null;
    }

    /**
     * @param int|string $id
     * @return mixed
     * @throws DbQueryException
     */
    public function find(int|string $id): mixed
    {
        $this->where('id', '=', $id);
        return $this->first();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $this->select('count(*) AS aggregate');

        return (int)$this->value('aggregate');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function max(string $column): mixed
    {
        $this->select(sprintf('MAX(%s) as aggregate', $column));

        return $this->value('aggregate');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function min(string $column): mixed
    {
        $this->select(sprintf('MIN(%s) as aggregate', $column));

        return $this->value('aggregate');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function avg(string $column): mixed
    {
        $this->select(sprintf('AVG(%s) as aggregate', $column));

        return $this->value('aggregate');
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function sum(string $column): mixed
    {
        $this->select(sprintf('SUM(%s) as aggregate', $column));

        return $this->value('aggregate');
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

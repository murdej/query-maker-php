<?php

namespace Murdej\QueryMaker\Common;

use http\Exception\InvalidArgumentException;

class ColumnCollection
{
    /** @var Column[] */
    public array $columns = [];

    public function addColumn(string|Identifier $column, string|null $alias = null, ?string $direction = null): ColumnCollection
    {
        $this->columns[] = new Column($column, $alias, null, $direction);
        return $this;
    }

    public function addPrefixed(string $table, string $prefix, array $columns): ColumnCollection
    {
        foreach ($columns as $column) {
            $this->addColumn(
                "$table.$column", $prefix.$column
            );
        }

        return $this;
    }

    public function addSnippet(string $alias, ?string $direction = null): Snippet
    {
        $col = new Column(new Snippet(), $alias, null, $direction);
        $this->columns[] = $col;
        return $col->column;
    }

    public function addSubSelect(string|null $tableName, string $alias, ?string $direction = null): DataSource
    {
        $col = new Column(null, $alias, new DataSource($this->query), $direction);
        if ($tableName) $col->dataSource->fromTable($tableName);
        $this->columns[] = $col;

        return $col->dataSource;
    }

    public function addExistsSubSelect(string|null $tableName, string $alias, ?string $direction = null): DataSource
    {
        $col = new Column(null, $alias, new DataSource($this->query), $direction, Column::SubQueryType_Exists);
        if ($tableName) $col->dataSource->fromTable($tableName);
        $this->columns[] = $col;

        return $col->dataSource;
    }

    public function any(): bool
    {
        return (bool)$this->columns;
    }

    public function __construct(
        public Query $query,
    )
    {
    }

    public function addColumns(string ...$columns)
    {
        foreach ($columns as $column) {
            if (preg_match('/(([a-zA-Z]+)\\.)?([a-zA-Z]+)( +([Aa][Ss] +)?([a-zA-Z]+))?/', $column, $m))
            {
                $this->columns[] = new Column(
                    (new Identifier($m[3], $m[2] ?: null))->fieldSnippet(),
                    $m[6] ?? null
                );
            } else throw  new InvalidArgumentException("Invalid column definition '$column'.");
        }
    }
}
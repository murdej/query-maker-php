<?php

namespace Murdej\QueryMaker\Common;

use Murdej\QueryMaker\Factory\FulltextSnipperFactory;

class OrderCollection extends ColumnCollection
{
    public function addAscColumn(string|Identifier $column, string|null $alias = null): ColumnCollection
    {
        return $this->addColumn($column, $alias, "ASC");
    }
    public function addAscSnippet(string $alias): Snippet
    {
        return $this->addSnippet($alias, "ASC");
    }
    public function addAscSubSelect(string|null $tableName, string $alias): DataSource
    {
        return $this->addSubSelect($tableName,  $alias, "ASC");
    }

    public function addDescColumn(string|Identifier $column, string|null $alias = null): ColumnCollection
    {
        return $this->addColumn($column, $alias, "DESC");
    }
    public function addDescSnippet(string $alias): Snippet
    {
        return $this->addSnippet($alias, "DESC");
    }
    public function addDescSubSelect(string|null $tableName, string $alias): DataSource
    {
        return $this->addSubSelect($tableName,  $alias, "DESC");
    }

    /**
     * @param string $str
     * @param (Identifier|SnippetChunk|string)[] $fields
     * @return $this
     */
    public function addFulltext(string $str, array|string $fields, string $direction, ?string $mode = null): ConditionCollection
    {
        $fulltext = new FulltextSnipperFactory();
        $fulltext->createFulltextSnipper(
            $this->addSnippet(),
            $str,
            $fields,
            $mode,
            false
        );
        return $this;
    }

    public function __construct(Query $query)
    {
        parent::__construct($query);
    }

    /* public function any()
    {
        return (bool)$this->columns;
    }*/
}
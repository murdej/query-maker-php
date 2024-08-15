<?php

namespace Murdej\QueryMaker\Common;

class CteCollection
{
    public function __construct(
        public Query $query,
    )
    {
    }

    public function add(string $alias, ?string $tableName = null): DataSource
    {
        $cte = new Cte($alias, new DataSource($this->query));
        if ($tableName) $cte->dataSource->fromTable($tableName);

        $this->ctes[] = $cte;

        return $cte->dataSource;
    }

    /** @var Cte[] */
    public array $ctes = [];

    public function any(): bool
    {
        return (bool)$this->ctes;
    }
}
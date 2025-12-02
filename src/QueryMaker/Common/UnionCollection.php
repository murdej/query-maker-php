<?php declare(strict_types=1);

namespace Murdej\QueryMaker\Common;

class UnionCollection
{
    public function __construct(
        public Query $query,
    )
    {
    }

    public function add(string $type = Union::Type_Distinct, ?string $tableName = null): DataSource
    {
        $union = new Union($type, new DataSource($this->query));
        if ($tableName) $union->dataSource->fromTable($tableName);

        $this->unions[] = $union;

        return $union->dataSource;
    }

    public function addAll(?string $tableName = null): DataSource
    {
        return $this->add(Union::Type_All, $tableName);
    }

    public function addDistinct(?string $tableName = null): DataSource
    {
        return $this->add(Union::Type_Distinct, $tableName);
    }

    /** @var Union[] */
    public array $unions = [];

    public function any(): bool
    {
        return (bool)$this->unions;
    }
}
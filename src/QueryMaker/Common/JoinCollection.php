<?php

namespace Murdej\QueryMaker\Common;

class JoinCollection
{
    /** @var Join[] */
    public $joins = [];

    public function add(string $type, Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
    {
        $join = new Join($type, $from, $on, $this->parentDataSource);
        $this->joins[] = $join;

        return $join;
    }

    public function addSubquery(string $type, string $subQueryAlias, string|ConditionCollection|Condition $on): Join
    {
        $join = new Join($type, new DataSource($this->parentDataSource->query), $on, $this->parentDataSource, "");
        $this->joins[] = $join;

        return $join;
    }

    public function addLeft(Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
    {
        return $this->add(Join::Type_Left, $from, $on);
    }

    public function addInner(Identifier|string|DataSource $from, string|ConditionCollection|Condition $on): Join
    {
        return $this->add(Join::Type_Left, $from, $on);
    }

    public function __construct(
        public DataSource $parentDataSource
    )
    { }
}
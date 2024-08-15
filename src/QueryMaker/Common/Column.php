<?php

namespace Murdej\QueryMaker\Common;

class Column
{

    public ?Snippet $column;

    public function __construct(
        string|Snippet|Identifier|null $column,
        public ?string $alias = null,
        public ?DataSource $dataSource = null,
        public ?string $direction = null,
        public ?string $subQueryType = null,
    )
    {
        if ($this->dataSource && !$this->subQueryType) $this->subQueryType = self::SubQueryType_Value;
        if (is_string($column)) {
            $this->column = Identifier::fromString($column)->fieldSnippet();
        } else if ($column instanceof Identifier) {
            $this->column = $column->fieldSnippet();
        } else {
            $this->column = $column;
        }
    }

    const SubQueryType_Exists = "Exists";
    const SubQueryType_Value = "Value";
}

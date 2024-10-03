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
            $column = trim($column);
            $uColumn = strtoupper($column);
            if (str_ends_with($uColumn, ' ASC')) {
                $this->direction = 'ASC';
                $column = substr($column, 0, -4);
            } else if (str_ends_with($uColumn, ' DESC')) {
                $column = substr($column, 0, -5);
                $this->direction = 'DESC';
            }
            $p = strpos($uColumn, ' AS ');
            if ($p !== false) {
                $column = trim(substr($column, 0, $p));
                $this->alias = trim(substr($column, $p + 4));
            }
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

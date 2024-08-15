<?php

namespace Murdej\QueryMaker\Common;

class Join
{
    public Identifier|DataSource $from;

    public ConditionCollection $on;


    public function __construct(
        public string $type,
        Identifier|string|DataSource $from,
        string|ConditionCollection|Condition $on,
        public DataSource $parentDataSource,
        public ?string $subQueryAlias = null,
    )
    {
        $this->from = is_string($from) ? Identifier::fromString($from) : $from;
        if (is_string($on)) {
            if (preg_match('/^([a-zA-Z0-9_]*):([a-zA-Z0-9_]+\\.[a-zA-Z0-9_]+)$/', $on, $m)) {
                $on = new ConditionCollection('and', $this->parentDataSource->query);
                $on->addEq(
                    new Identifier($m[1] ?: "id", ($this->from->alias ?: $this->from->name)),
                    Identifier::fromString($m[2]),
                );
            } else if (preg_match('/^([a-zA-Z0-9_]*):([a-zA-Z0-9_]*)$/', $on, $m)) {
                $on = new ConditionCollection('and', $this->parentDataSource->query);
                $on->addEq(
                    new Identifier($m[1] ?: "id", ($this->from->alias ?: $this->from->name)),
                    new Identifier($m[2] ?: "id", $this->parentDataSource->from->alias ?: $this->parentDataSource->from->name),
                );
            } else if (preg_match('/^([a-zA-Z_]?[a-zA-Z0-9_]*)$/', $on, $m)) {
                $on = new ConditionCollection('and', $this->parentDataSource->query);
                $on->addEq(
                    new Identifier($m[1], $this->from->alias),
                    new Identifier($m[1], $this->parentDataSource->from->alias),
                );
            } else {
                $cc = new ConditionCollection('and', $this->parentDataSource->query);
                $cc->addSnippet()->code($on);
                $on = $cc;
            }
            $this->on = $on;
        }
    }

    const Type_Left = "Left";
    const Type_Right = "Right";
    const Type_Inner = "Inner";
    const Type_Outer = "Outer";
}
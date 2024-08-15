<?php

namespace Murdej\QueryMaker\Common;

class Condition
{
    public DataSource $subQuery;

    public function __construct(
        public ?string $operator = null,
        public SnippetChunk|Snippet|null $a = null,
        public SnippetChunk|Snippet|null $b = null,
        public SnippetChunk|Snippet|null $c = null,
        public ?Snippet $snippet = null,
    )
    {
    }

    const Operator_eq = "=";
    const Operator_neq = "!=";
    const Operator_gt = ">";
    const Operator_lt = "<";
    const Operator_gte = ">=";
    const Operator_lte = "<=";
    const Operator_like = "LIKE";
    const Operator_in = "IN";
    const Operator_range = "range";
    const Operator_inSubQuery = "inSubQuery";
    const Operator_existsSubQuery = "existsSubQuery";
    const Operator_isNull = "IS NULL";
    const Operator_notNull = "NOT NULL";

    public static function false(): Condition
    {
        $cond = new Condition(self::Operator_eq, SnippetChunk::code("0"), SnippetChunk::code("1"));
        return $cond;
    }

    public function getBArray(): array
    {
        return ($this->b instanceof SnippetChunk)
            ? array_map(fn($item) => SnippetChunk::value($item), $this->b->value)
            : $this->b->content;
    }

}
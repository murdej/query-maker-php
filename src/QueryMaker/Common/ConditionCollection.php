<?php

namespace Murdej\QueryMaker\Common;

use Murdej\QueryMaker\Factory\FulltextSnipperFactory;

class ConditionCollection
{
    /** @var Condition|Snippet|ConditionCollection[] */
    public $conds = [];


    const Operation_And = "AND";

    const Operation_Or = "OR";

    public function __construct(
        public string $operation,
        public Query $query,
    )
    {
    }

    public function addSnippet(): Snippet
    {
        $cond = new Condition();
        $cond->snippet = new Snippet();
        $this->conds[] = $cond;

        return $cond->snippet;
    }

    public function addSubconditions(string $operation): ConditionCollection
    {
        return $this->conds[] = new ConditionCollection($operation, $this->query);
    }

    public function addEq(Identifier|SnippetChunk|string $a, /*Identifier|SnippetChunk|*/mixed $b): ConditionCollection
    {
        $this->conds[] = new Condition(
            Condition::Operator_eq,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
        );

        return $this;
    }

    public function addNotEq(Identifier|SnippetChunk|string $a, /*Identifier|SnippetChunk|*/mixed $b): ConditionCollection
    {
        $this->conds[] = new Condition(
            Condition::Operator_neq,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
        );

        return $this;
    }

    public function addIn(Identifier|SnippetChunk|string $a, /*Identifier|SnippetChunk|*/array|Snippet $b): ConditionCollection
    {
        $this->conds[] = new Condition(
            Condition::Operator_in,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
        );

        return $this;
    }

    public function addNotIn(Identifier|SnippetChunk|string $a, /*Identifier|SnippetChunk|*/array|Snippet $b): ConditionCollection
    {
        $this->conds[] = new Condition(
            Condition::Operator_notIn,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
        );

        return $this;
    }

    public function addMultiLike(string $str, Identifier|SnippetChunk|string ...$fields): ConditionCollection
    {
        foreach ($fields as $f) {
            $this->addLike($f, $str);
        }

        return $this;
    }

    /**
     * @param string $str
     * @param (Identifier|SnippetChunk|string)[] $fields
     * @return $this
     */
    public function addFulltext(string $str, array|string $fields, ?string $mode = null): ConditionCollection
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

    public function addLike(Identifier|SnippetChunk|string $a, /*Identifier|SnippetChunk|*/string $b): ConditionCollection
    {
        $this->conds[] = new Condition(
            Condition::Operator_like,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
        );

        return $this;
    }

    public function addMultiIn(array $cols, array $values): ConditionCollection
    {
        if (count($values) == 0) {
            $this->conds[] = new Condition(null, null, null, null, (new Snippet())->code('1=0 /* ' . implode(', ', $cols) . ' */'));
        } else {

            $cond = new Snippet();
            $cond->code("(")->beginList(", ");

            foreach ($cols as $col)
            {
                if (is_string($col)) $col = Identifier::fromString($col);
                if ($col instanceof Identifier) $col = $col->fieldSnippet();
                $cond->paste($col);
            }
            $cond->endList()->code(") IN (");
            $f = true;
            foreach ($values as $vals) {
                if ($f) $f = false;
                else $cond->code(", ");
                if (array_key_exists(0, $vals)) $vals = array_combine($cols, $vals);
                $f1 = true;
                $cond->code("(");
                foreach ($cols as $col)
                {
                    if ($f1) $f1 = false;
                    else $cond->code(", ");
                    $v = $vals[$col];
                    if ($v instanceof Snippet || $v instanceof SnippetChunk) $cond->add($v);
                    else $cond->value($v);
                }
                $cond->code(")");
            }
            $cond->code(")");

            $this->conds[] = new Condition(null, null, null, null, $cond);
        }

        return $this;
    }

    public function addRange(mixed $val, mixed $min, mixed $max, bool $nullIsUnlimited = false, bool $includingMin = true, bool $includingMax = true): ConditionCollection
    {
        if ($val === null) {
            if (!$nullIsUnlimited) {
                $cond = Condition::false();
            }
            return $this;
        }
        $cond = new ConditionCollection(ConditionCollection::Operation_And, $this->query);

        $c2 = $cond;
        if ($nullIsUnlimited && $min instanceof Identifier) {
            $c2 = $cond->addSubconditions(ConditionCollection::Operation_Or);
            $c2->addIsNull($min);
        }
        if ($min !== null) $c2->add($val, $includingMin ? Condition::Operator_gte : Condition::Operator_gt, $min);

        $c2 = $cond;
        if ($nullIsUnlimited && $max instanceof Identifier) {
            $c2 = $cond->addSubconditions(ConditionCollection::Operation_Or);
            $c2->addIsNull($max);
        }
        if ($max !== null) $c2->add($val, $includingMax ? Condition::Operator_lte : Condition::Operator_lt, $max);

        $this->conds[] = $cond;

        return $this;
    }

    public function add(/*Identifier|SnippetChunk|Condition|string*/mixed $a, string $operator, /*Identifier|SnippetChunk|*/mixed $b, mixed $c = null): self
    {
        $this->conds[] = new Condition(
            $operator,
            $this->toOperand($a, true),
            $this->toOperand($b, false),
            $this->toOperand($c, false),
        );

        return $this;
    }

    /**
     * Add multiple conditions from array:
     *  - `'SQL code'` add sql code condition
     *  - `'column' => [ ... ]` add `column IN [ ... ]`
     *  - `'!column' => [ ... ]` add `column NOT IN [ ... ]`
     *  - `'column' => null` add `column IS NULL`
     *  - `'!column' => null` add `column IS NOT NULL`
     *  - `'column' => any_value` add `column = any_value`
     *  - `'!column' => any_value` add `column != any_value`
     * 
     * @param array $conditions
     * @return self
     */
    public function addMulti(array $conditions): self
    {
        foreach ($conditions as $k => $v) {
            if (is_int($k)) {
                $this->addSnippet()->code($v);
            } else {
                if (str_contains($k, '?')) {
                    throw new Exception('? not implemented');
                    // $this->addSnippet()-> code($v, );
                } else if (is_array($v)) {
                    if ($k[0] === '!')
                        $this->addNotIn(substr($k, 1), $v);
                    else
                        $this->addIn($k, $v);
                    
                } else if ($v === null) {
                    if ($k[0] === '!')
                        $this->addNotNull(substr($k, 1), $v);
                    else
                        $this->addIsNull($k, $v);
                } else {
                    if ($k[0] === '!')
                        $this->addNotEq(substr($k, 1), $v);
                    else
                        $this->addEq($k, $v);
                }
            }
        }
        return $this;
    }

    private function toOperand(mixed $b, bool $stringIsField): SnippetChunk|Snippet|null
    {
        if ($b instanceof SnippetChunk || $b instanceof Snippet) {
            return $b;
        } else if ($b instanceof Identifier) {
            return $b->fieldSnippet();
        } else if ($stringIsField && is_string($b)) {
            $idn = Identifier::fromString($b);
            return $idn->fieldSnippet();
        } else return SnippetChunk::value($b);
    }

    public function any(): bool
    {
        foreach ($this->conds as $cond)
        {
            if ($cond instanceof ConditionCollection) return $cond->any();
            else return true;
        }
        return false;
    }

    public function addExists(): DataSource
    {
        $cond = new Condition(Condition::Operator_existsSubQuery);
        $cond->subQuery = new DataSource($this->query);
        $this->conds[] = $cond;
        return $cond->subQuery;
    }

    public function addIsNull(Identifier|SnippetChunk|Condition|string $a): self
    {
        $this->conds[] = new Condition(
            Condition::Operator_isNull,
            $this->toOperand($a, true),
        );

        return $this;
    }

    public function addNotNull(Identifier|SnippetChunk|Condition|string $a): self
    {
        $this->conds[] = new Condition(
            Condition::Operator_notNull,
            $this->toOperand($a, true),
        );

        return $this;
    }
}
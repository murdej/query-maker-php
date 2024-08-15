<?php

namespace Murdej\QueryMaker\Maker;

use Murdej\QueryMaker\Common\Column;
use Murdej\QueryMaker\Common\ColumnCollection;
use Murdej\QueryMaker\Common\Condition;
use Murdej\QueryMaker\Common\ConditionCollection;
use Murdej\QueryMaker\Common\Cte;
use Murdej\QueryMaker\Common\CteCollection;
use Murdej\QueryMaker\Common\DataSource;
use Murdej\QueryMaker\Common\Identifier;
use Murdej\QueryMaker\Common\Join;
use Murdej\QueryMaker\Common\JoinCollection;
use Murdej\QueryMaker\Common\Query;
use Murdej\QueryMaker\Common\Snippet;
use Murdej\QueryMaker\Common\SnippetChunk;

abstract class BaseMaker implements IMaker
{

    public Snippet $content;

    public function __construct()
    {
        $this->content = new Snippet();
    }

    public function makeQuery(Query $query): QueryAndValues {
        //CTE
        $this->makeCtes($query->ctes);
        // SQL
        $this->makeDataSource($query);
        return $this->makeSnippetQuery($this->content);
    }

    public function makeDataSource(DataSource $dataSource)
    {
        $cnt = $this->content;
        if ($dataSource->snippet) {
            $cnt->add($dataSource->snippet);
        } else {
            $cnt->code("SELECT ");
            if ($dataSource instanceof Query && $dataSource->prepareObtainCount)
                $cnt->code("SQL_CALC_FOUND_ROWS ");
            // columns
            $this->makeColumns($dataSource->columns);

            if ($dataSource->from) {
                $cnt->endl()->code("FROM ");
                if ($dataSource->from instanceof Identifier) {
                    $cnt->identifier($dataSource->from->name);
                    if ($dataSource->from->alias) $cnt->code(" ")->identifier($dataSource->from->alias);
                } else if ($dataSource->from instanceof DataSource) {
                    $cnt->code("(")->endl(1);
                    $this->makeDataSource($dataSource->from);
                    $cnt->endl(-1)->code(")")->endl();
                }
            }

            // joins
            $this->makeJoins($dataSource->joins);
            // where
            $this->makeConditions($dataSource->conditions);
            // having
            $this->makeConditions($dataSource->havings, "HAVING");
            // group
            if ($dataSource->groups->any()) {
                $cnt->code("GROUP BY ");
                $this->makeColumns($dataSource->groups);
                $cnt->endl();
            }
            // order
            if ($dataSource->orders->any()) {
                $cnt->code("ORDER BY ");
                $this->makeColumns($dataSource->orders);
                $cnt->endl();
            }

            // limit
            if ($dataSource instanceof Query && $dataSource->limitCount) {
                $cnt->endl();
                $cnt->code("LIMIT " . $dataSource->limitFrom . ", " . $dataSource->limitCount);
            }

        }
    }

    public function makeConditions(ConditionCollection $conditions, string $section = "WHERE")
    {
        if ($conditions->any()) {
            $cnt = $this->content;
            $cnt->endl()->code("$section ")->endl(1);
            $f = true;
            foreach ($conditions->conds as $cond) {
                if ($f) $f = false;
                else $cnt->endl()->code(" " .  $conditions->operation . " ");
                $cnt->code("(");
                $this->makeCondition($cond);
                $cnt->code(")")->endl(); //;
            }
            $cnt->endl(-1);
        }
    }

    public function makeCondition(Condition|ConditionCollection|Snippet $cond)
    {
        $cnt = $this->content;
        if ($cond instanceof ConditionCollection) {
            if ($cond->any()) {
                $cnt->code("(")->endl(1);
                $this->makeConditions($cond, '');
                $cnt->endl(-1)->code(")");
            }
        }
        else if ($cond instanceof Condition) {

            if ($cond->snippet) $cnt->paste($cond->snippet);
            else if (in_array($cond->operator, [Condition::Operator_eq, Condition::Operator_neq, Condition::Operator_gt,
                Condition::Operator_lt, Condition::Operator_gte, Condition::Operator_lte, Condition::Operator_like])) {
                $cnt->add($cond->a);
                $cnt->code(" " . $cond->operator . " ");
                $cnt->add($cond->b);
            } else if (in_array($cond->operator, [Condition::Operator_isNull, Condition::Operator_notNull])) {
                $cnt->add($cond->a);
                $cnt->code(" " . $cond->operator . " ");
            } else if ($cond->operator === Condition::Operator_in) {
                $valuesPN = $cond->getBArray();
                $useNull = false;
                $values = [];
                foreach ($valuesPN as $v) {
                    if ($v instanceof SnippetChunk && $v->type === SnippetChunk::Type_value && $v->value === null) {
                        $useNull = true;
                    } else {
                        $values[] = $v;
                    }
                }
                if ($values || $useNull) {
                    $both = $values && $useNull;
                    if ($both) $cnt->code("(");
                    if ($values) {
                        $cnt->add($cond->a);
                        $cnt->code(" IN (");
                        $f = true;
                        foreach ($values as $item) {
                            if ($f) $f = false;
                            else $cnt->code(", ");
                            $cnt->add($item);
                        }
                        $cnt->code(")");
                    }
                    if ($both) $cnt->code(" OR ");
                    if ($useNull) {
                        $cnt->add($cond->a);
                        $cnt->code(" IS NULL");
                    }
                    if ($both) $cnt->code(")");
                } else {
                    $cnt->code('1=0 /* ');
                    $cnt->add($cond->a);
                    $cnt->code(' */');
                }
            } else if ($cond->operator === Condition::Operator_range) {
                //TODO
            } else if ($cond->operator === Condition::Operator_inSubQuery) {
                $cnt->add($cond->a);
                $cnt->code(" IN (")->endl(1);
                $this->makeDataSource($cond->subQuery);
                $cnt->endl(-1)->code(")");
            } else if ($cond->operator === Condition::Operator_existsSubQuery) {
                $cnt->code("EXISTS(")->endl(1);
                $this->makeDataSource($cond->subQuery);
                $cnt->endl(-1)->code(")");
            }
        }
    }

    public function makeSnippetQuery(Snippet $snippet): QueryAndValues
    {
        $res = new QueryAndValues();
        foreach ($snippet->content as $chunk) {
            switch ($chunk->type)
            {
                case SnippetChunk::Type_code:
                    $res->query .= $chunk->content;
                    break;
                case SnippetChunk::Type_value:
                    //todo: dle db
                    $res->query .= '?';
                    $res->values[] = $chunk->value;
                    break;
                case SnippetChunk::Type_identifier:
                    $res->query .= $this->escapeIdentifier($chunk->content);
                    break;
            }
        }

        return $res;
    }

    public function makeColumns(ColumnCollection $columns) {
        if ($columns->any()) {
            $f = true;
            foreach ($columns->columns as $column) {
                if ($f) $f = false;
                else $this->content->code(", ");
                $this->makeColumn($column);
            }
        } else {
            $this->content->code("*");
        }
    }

    public function makeColumn(Column $column)
    {
        $cnt = $this->content;
        if ($column->column instanceof Identifier) {
            $cnt->paste($column->column->fieldSnippet());
        } else if ($column->column instanceof Snippet) {
            $cnt->paste($column->column);
        } else if ($column->dataSource) {
            $cnt->code(
                ($column->subQueryType === Column::SubQueryType_Exists ? "EXISTS" : "")
                . "("
            )->endl(1);
            $this->makeDataSource($column->dataSource);
            $cnt->endl(-1)->code(")");
        }
        if ($column->alias) {
            $cnt->code(" AS ")->identifier($column->alias);
        }
        if ($column->direction) {
            $cnt->code(" $column->direction ");
        }
    }

    public function makeCtes(CteCollection $ctes)
    {
        $cnt = $this->content;
        if ($ctes->any()) {
            $cnt->code("WITH")->endl(1);
            $f = true;
            foreach ($ctes->ctes as $cte) {
                if ($f) $f = false;
                else $cnt->code(", ");
                $this->makeCte($cte);
            }
            $cnt->endl(-1);
        }
    }

    public function makeCte(Cte $cte)
    {
        $cnt = $this->content;
        $cnt->endl()->identifier($cte->alias)->code(" AS (")->endl(1);
        $this->makeDataSource($cte->dataSource);
        $cnt->endl(-1)->code(")");
    }

    public function makeJoins(JoinCollection $joins)
    {
        foreach ($joins->joins as $join)
            $this->makeJoin($join);
    }

    public function makeJoin(Join $join)
    {
        $cnt = $this->content;
        $cnt->endl()->code(strtoupper($join->type) . " JOIN ");
        if ($join->from instanceof Identifier) {
            $cnt->paste($join->from->tableSnippet());
        } else if ($join->from instanceof DataSource) {
            $cnt->code(" (")->endl(1);
            $this->makeDataSource($join->from);
            $cnt->endl(-1)->code(") ")->identifier($join->subQueryAlias);
        }
        if ($join->on) {
            $cnt->endl(1);
            $this->makeConditions($join->on, "ON");
            $cnt->endl(-1);
        }
        $cnt->endl();
    }
}
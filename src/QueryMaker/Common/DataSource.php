<?php

declare(strict_types=1);

namespace Murdej\QueryMaker\Common;

use Murdej\QueryMaker\Factory\FulltextSnipperFactory;

class DataSource
{
	public function __construct(
		public Query $query,
	)
	{
		$this->conditions = new ConditionCollection('and', $this->query);
        $this->joins = new JoinCollection($this);
        $this->columns = new ColumnCollection($this->query);
        $this->groups = new ColumnCollection($this->query);
        $this->orders = new OrderCollection($this->query);
        $this->havings = new ConditionCollection('and', $this->query);
	}

	public DataSource|Identifier|null $from = null;

    public ConditionCollection $conditions;

    public ConditionCollection $havings;

    public JoinCollection $joins;

    public ColumnCollection $columns;

    public ColumnCollection $groups;

    public OrderCollection $orders;

    public ?Snippet $snippet = null;

	public ?string $alias = null;

	public function fromTable(string|Identifier $tableName, ?string $alias = null): DataSource {
        if (is_string($tableName)) $tableName = Identifier::fromString($tableName);
		$this->from = $tableName;
		$this->alias = $alias;

		return $this;
	}

	public function fromSubSelect(string $alias = null): DataSource {
		$this->from = new DataSource($this->query);
		$this->alias = $alias;

		return $this->from;
	}

    public function useSnippet(?Snippet $snippet = null): Snippet
    {
        $this->snippet = $snippet ? $snippet : new Snippet();
        return $this->snippet;
    }

    /**
     * @param string $str
     * @param (Identifier|SnippetChunk|string)[] $fields
     * @return $this
     */
    public function addFulltextConditionAndOrder(string $str, array|string $fields, ?string $mode = null): ConditionCollection
    {
        $this->conditions->addFulltext($str, $fields, $mode);
        $this->orders->addFulltext($str, $fields, $mode);

        return $this;
    }

}

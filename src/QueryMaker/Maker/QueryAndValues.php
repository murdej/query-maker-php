<?php

namespace Murdej\QueryMaker\Maker;

class QueryAndValues
{
    public string $query = "";

    public array $values = [];

    public function add(QueryAndValues $queryAndValues)
    {
        $this->query .= $queryAndValues->query;
        $this->values = array_merge($this->values, $queryAndValues->values);
    }
}
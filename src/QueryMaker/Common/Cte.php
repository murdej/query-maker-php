<?php

namespace Murdej\QueryMaker\Common;

class Cte
{
    public function __construct(
        public string $alias,
        public DataSource $dataSource,
    )
    {
    }
}
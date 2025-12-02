<?php

namespace Murdej\QueryMaker\Common;

class Union
{
    public function __construct(
        public string $type,
        public DataSource $dataSource,
    )
    {
    }

    public const string Type_All = 'ALL';
    public const string Type_Distinct = 'DISTINCT';
}
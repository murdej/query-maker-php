<?php

namespace Murdej\QueryMaker\Maker;

use Murdej\QueryMaker\Common\DataSource;

interface IMaker
{
    function escapeIdentifier(string $identifier);

    function makeDataSource(DataSource $dataSource);
}
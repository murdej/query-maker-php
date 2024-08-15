<?php

namespace Murdej\QueryMaker\Maker;

class MariaDB extends BaseMaker
{

    function escapeIdentifier(string $identifier)
    {
        return $identifier === "*" ? $identifier : "`$identifier`";
    }
}
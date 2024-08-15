<?php

declare(strict_types=1);

namespace Murdej\QueryMaker\Common;

class Query extends DataSource 
{
    public CteCollection $ctes;

    public ?int $limitFrom = 0;

    public ?int $limitCount = null;

    public bool $prepareObtainCount = false;

    public function __construct()
    {
        parent::__construct($this);
        $this->ctes = new CteCollection($this);
    }
}

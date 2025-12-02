<?php declare(strict_types=1);

namespace Murdej\QueryMaker\Common;

class Fulltext
{
    public function __construct(
        public string $searchTerm,
        public array $columns,
        public string $mode = self::Mode_Natural,
    )
    {
    }

    public const string Mode_Natural = 'IN NATURAL LANGUAGE MODE';
    public const string Mode_NaturalQueryExpansion = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';
    public const string Mode_Boolean = 'IN BOOLEAN MODE';
    public const string Mode_QueryExpansion = 'WITH QUERY EXPANSION';
}
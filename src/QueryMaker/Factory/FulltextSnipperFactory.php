<?php

namespace Murdej\QueryMaker\Factory;

use Murdej\QueryMaker\Common\Identifier;
use Murdej\QueryMaker\Common\Snippet;
use Murdej\QueryMaker\Common\SnippetChunk;

class FulltextSnipperFactory
{
    /**
     * @param string $str
     * @param (Identifier|SnippetChunk|string)[] $fields
     * @return $this
     */
    public function createFulltextSnipper(Snippet $snippet, string $str, array $fields, ?string $mode, bool $addRelevance): void
    {
        $fieldWithRels = [];
        foreach ($fields as $k => $v) {
            if (is_string($k)) $fieldWithRels[$k] = $v;
            else $fieldWithRels[$v] = 1;
        }

        $snippet->code('MATCH(');
        $f = true;
        foreach ($fields as $field => $relevance) {
            if ($f) $f = false;
            else $snippet->code(', ');
            $snippet->add($field);
            if ($addRelevance && ($relevance != 1)) $snippet->code(' * ' . $relevance);
        }
        $snippet->code(') AGAINST(')->value($str);
        if ($mode) $snippet->code(' ' . $mode);
        $snippet->code(')');
    }

    const Mode_Boolean = 'IN BOOLEAN MODE';

    const Mode_Natural = 'IN NATURAL LANGUAGE MODE';

    const Mode_QueryExpansion = 'WITH QUERY EXPANSION';
}
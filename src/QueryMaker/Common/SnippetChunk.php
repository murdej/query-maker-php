<?php

namespace Murdej\QueryMaker\Common;

class SnippetChunk
{
    public function __construct(
        public string $type,
        public ?string $content,
        public mixed $value = null,
    ) { }

    const Type_code = "code";
    const Type_value = "value";
    const Type_identifier = "identifier";

    static function value(mixed $value) {
        return new self(self::Type_value, null, $value);
    }

    static function code(string $value) {
        return new self(self::Type_code, null, $value);
    }
}
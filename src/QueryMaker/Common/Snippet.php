<?php

declare(strict_types = 1);

namespace Murdej\QueryMaker\Common;

use SlevomatCodingStandard\Helpers\SniffSettingsHelper;

class Snippet {
    /** @var SnippetChunk[] */
    public array $content = [];

    public int $indentLevel = 0;
    /**
     * @var false
     */
    private bool $prevEndl = true;

    protected bool $isFirst = true;

    private string|null $separator = null;

    public function code(string $code): Snippet {
        $this->beforeAddContent();
        $this->content[] = new SnippetChunk(SnippetChunk::Type_code, $code);
        return $this;
    }

    public function identifier(string $code): Snippet {
        $this->beforeAddContent();
        $this->content[] = new SnippetChunk(SnippetChunk::Type_identifier, $code);
        return $this;
    }

    public function value(mixed $value, string $name = null): Snippet {
        $this->beforeAddContent();
        $this->content[] = new SnippetChunk(SnippetChunk::Type_value, $name, $value);
        return $this;
    }

    public function endl(int $intendChange = 0)
    {
        $this->indentLevel += $intendChange;
        if (!$this->prevEndl) {
            $this->content[] = new SnippetChunk(SnippetChunk::Type_code,
                "\n" . str_repeat("\t", $this->indentLevel));
        }
        $this->prevEndl = true;
        return $this;
    }

    public function paste(Snippet $snippet)
    {
        $this->beforeAddContent();
        $this->content = array_merge($this->content, $snippet->content);
    }

    public function add(Snippet|SnippetChunk|null $a)
    {
        if ($a instanceof Snippet) $this->paste($a);
        else $this->content[] = $a;
    }

    public function beginList(string $separator): Snippet {
        $this->separator = $separator;
        $this->isFirst = true;
        return $this;
    }

    public function endList(): Snippet {
        $this->separator = null;
        return $this;
    }

    protected function beforeAddContent() {
        if ($this->separator) {
            if ($this->isFirst) {
                $this->isFirst = false;
            } else {
                $this->content[] = new SnippetChunk(SnippetChunk::Type_code, $this->separator);
            }
        }
        $this->prevEndl = false;
    }

}
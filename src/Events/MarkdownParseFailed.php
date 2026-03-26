<?php

namespace Ranetrace\Lemme\Events;

class MarkdownParseFailed
{
    public function __construct(
        public string $filepath,
        public string $error,
    ) {}
}

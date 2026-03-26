<?php

namespace Ranetrace\Lemme\Tests\Support;

use Illuminate\Support\Facades\File;

class DocsFactory
{
    protected string $relativePath;

    protected function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
        File::ensureDirectoryExists(base_path($this->relativePath));
    }

    public static function make(): self
    {
        // Unique per test run (sufficiently random for parallel safety)
        $id = bin2hex(random_bytes(6));

        return new self('tests/runtime/docs_'.$id);
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function file(string $relative, string $content): self
    {
        $fullDir = dirname(base_path($this->relativePath.'/'.$relative));
        File::ensureDirectoryExists($fullDir);
        File::put(base_path($this->relativePath.'/'.$relative), $content);

        return $this;
    }

    public function markdown(string $relative, string $title, string $body = ''): self
    {
        $content = <<<MD
---
title: {$title}
---

{$body}
MD;

        return $this->file($relative, $content);
    }

    public function cleanup(): void
    {
        File::deleteDirectory(base_path($this->relativePath));
    }
}

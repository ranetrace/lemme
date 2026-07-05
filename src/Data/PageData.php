<?php

namespace Ranetrace\Lemme\Data;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements ArrayAccess<string, mixed>
 */
class PageData implements Arrayable, ArrayAccess
{
    /**
     * @param  array<int, array{id:string,text:string,level:int,class:string}>  $headings
     * @param  array<string, mixed>  $frontmatter
     */
    public function __construct(
        public string $title,
        public string $slug,
        public string $raw_content, // original markdown (no injected heading HTML)
        public array $headings,
        public array $frontmatter,
        public string $filepath,
        public string $relative_path,
        public int $modified_at,
        public int $created_at,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'raw_content' => $this->raw_content,
            'headings' => $this->headings,
            'frontmatter' => $this->frontmatter,
            'filepath' => $this->filepath,
            'relative_path' => $this->relative_path,
            'modified_at' => $this->modified_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Rehydrate a PageData from its array form. Inverse of toArray(), so the
     * repository can cache pages as plain arrays and reconstruct them on read.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            slug: $data['slug'],
            raw_content: $data['raw_content'],
            headings: $data['headings'],
            frontmatter: $data['frontmatter'],
            filepath: $data['filepath'],
            relative_path: $data['relative_path'],
            modified_at: $data['modified_at'],
            created_at: $data['created_at'],
        );
    }

    // ArrayAccess ---------------------------------------------------------
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('PageData is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('PageData is immutable');
    }
}

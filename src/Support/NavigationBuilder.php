<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Collection;
use Ranetrace\Lemme\Data\PageData;

/**
 * Builds hierarchical navigation structure from a pages collection.
 */
class NavigationBuilder
{
    /**
     * Build navigation collection from pages.
     *
     * @param  Collection<string, PageData>  $pages  Pages keyed by slug (keys ignored for ordering).
     * @param  callable(string $slug): string  $urlResolver
     * @return Collection<array-key, mixed> Grouped navigation is a list; the ungrouped branch
     *                                      maps the pages collection and so keeps its slug
     *                                      keys. Nothing reads the keys either way.
     */
    public function build(Collection $pages, callable $urlResolver): Collection
    {
        if (! config('lemme.navigation.grouping.enabled', true)) {
            return $pages->map(function ($page) use ($urlResolver) {
                return [
                    'type' => 'page',
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'url' => $urlResolver($page['slug']),
                ];
            });
        }

        $grouped = $this->groupPagesByDirectory($pages);

        return $this->buildNavigationTree($grouped, $urlResolver);
    }

    /**
     * @param  Collection<string, PageData>  $pages
     * @return array<string, mixed>
     */
    protected function groupPagesByDirectory(Collection $pages): array
    {
        $grouped = [];

        foreach ($pages as $page) {
            $pathParts = explode('/', $page['relative_path']);
            array_pop($pathParts); // remove filename

            if (empty($pathParts)) {
                $grouped['_root'][] = $page;

                continue;
            }

            $grouped = $this->insertPageIntoTree($grouped, $pathParts, $page);
        }

        return $grouped;
    }

    /**
     * Recursively insert a page into the navigation tree without using references.
     *
     * @param  array<string,mixed>  $tree
     * @param  array<int,string>  $dirs
     * @return array<string,mixed>
     */
    protected function insertPageIntoTree(array $tree, array $dirs, PageData|array $page): array
    {
        if (empty($dirs)) {
            if (! isset($tree['_pages'])) {
                $tree['_pages'] = [];
            }
            $tree['_pages'][] = $page;

            return $tree;
        }

        $dir = array_shift($dirs);
        $subtree = $tree[$dir] ?? [];
        $tree[$dir] = $this->insertPageIntoTree($subtree, $dirs, $page);

        return $tree;
    }

    protected function buildNavigationTree(array $grouped, callable $urlResolver): Collection
    {
        $navigation = collect();

        if (isset($grouped['_root'])) {
            foreach ($grouped['_root'] as $page) {
                $navigation->push([
                    'type' => 'page',
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'url' => $urlResolver($page['slug']),
                ]);
            }
            unset($grouped['_root']);
        }

        foreach ($this->sortGroups($grouped) as $groupName => $groupData) {
            $navigation->push($this->buildNavigationGroup($groupName, $groupData, $urlResolver));
        }

        return $navigation;
    }

    protected function buildNavigationGroup(string $groupName, array $groupData, callable $urlResolver): array
    {
        $group = [
            'type' => 'group',
            'title' => PathLabel::for($groupName),
            'slug' => $groupName,
            'children' => collect(),
        ];

        if (isset($groupData['_pages'])) {
            $sortedPages = collect($groupData['_pages'])->sortBy(function ($page) {
                $filename = basename($page['relative_path']);

                return $this->getSortableDirectoryName($filename);
            });
            foreach ($sortedPages as $page) {
                $group['children']->push([
                    'type' => 'page',
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'url' => $urlResolver($page['slug']),
                ]);
            }
            unset($groupData['_pages']);
        }

        foreach ($this->sortGroups($groupData) as $nestedGroupName => $nestedGroupData) {
            $group['children']->push($this->buildNavigationGroup($nestedGroupName, $nestedGroupData, $urlResolver));
        }

        return $group;
    }

    /**
     * Order the groups of one level by directory name, number prefix first, in
     * the configured direction.
     *
     * @param  array<string, mixed>  $groups
     * @return Collection<string, mixed>
     */
    protected function sortGroups(array $groups): Collection
    {
        $descending = strtolower((string) config('lemme.navigation.grouping.sort_groups_direction', 'asc')) === 'desc';

        return collect($groups)->sortBy(
            fn (array $group, string $directoryName): string => $this->getSortableDirectoryName($directoryName),
            SORT_REGULAR,
            $descending,
        );
    }

    protected function getSortableDirectoryName(string $dirName): string
    {
        return $this->prefixSortableName($dirName);
    }

    protected function prefixSortableName(string $name): string
    {
        if (preg_match('/^(\d+)[-_](.+)/', $name, $matches)) {
            $number = str_pad($matches[1], 5, '0', STR_PAD_LEFT);
            $clean = $matches[2];

            return $number.'_'.$clean;
        }

        return '99999_'.$name;
    }
}

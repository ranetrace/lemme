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
     * @return Collection<int, mixed>
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

        $sortBy = config('lemme.navigation.grouping.sort_groups_by', 'directory_name');
        $sortDirection = config('lemme.navigation.grouping.sort_groups_direction', 'asc');

        $sortedGroups = collect($grouped);
        if ($sortBy === 'directory_name') {
            $sortedGroups = $sortedGroups->sortBy(function ($value, $key) {
                return $this->getSortableDirectoryName($key);
            }, SORT_REGULAR, $sortDirection === 'desc');
        }

        foreach ($sortedGroups as $groupName => $groupData) {
            $navigation->push($this->buildNavigationGroup($groupName, $groupData, $urlResolver));
        }

        return $navigation;
    }

    protected function buildNavigationGroup(string $groupName, array $groupData, callable $urlResolver): array
    {
        $group = [
            'type' => 'group',
            'title' => $this->formatGroupTitle($groupName),
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

        $sortedNestedGroups = collect($groupData)->sortBy(function ($value, $key) {
            return $this->getSortableDirectoryName($key);
        });

        foreach ($sortedNestedGroups as $nestedGroupName => $nestedGroupData) {
            $group['children']->push($this->buildNavigationGroup($nestedGroupName, $nestedGroupData, $urlResolver));
        }

        return $group;
    }

    protected function formatGroupTitle(string $dirName): string
    {
        $cleaned = $this->removeNumberPrefix($dirName);
        $formatted = str_replace(['-', '_'], ' ', $cleaned);

        return ucwords(strtolower($formatted));
    }

    protected function getSortableDirectoryName(string $dirName): string
    {
        return $this->prefixSortableName($dirName);
    }

    protected function removeNumberPrefix(string $name): string
    {
        return (string) preg_replace('/^\d+[-_]/', '', $name);
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

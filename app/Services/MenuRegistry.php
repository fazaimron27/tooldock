<?php

namespace App\Services;

class MenuRegistry
{
    /**
     * @var array<string, array<int, array{label: string, route: string, icon: string, order: int}>>
     */
    private array $menus = [];

    /**
     * Register a menu item.
     */
    public function registerItem(
        string $group,
        string $label,
        string $route,
        string $icon,
        ?int $order = null
    ): void {
        $order = $order ?? count($this->menus[$group] ?? []) * 10;

        if (! isset($this->menus[$group])) {
            $this->menus[$group] = [];
        }

        $this->menus[$group][] = [
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'order' => $order,
        ];
    }

    /**
     * Get all registered menus grouped and sorted.
     *
     * @return array<string, array<int, array{label: string, route: string, icon: string, order: int}>>
     */
    public function getMenus(): array
    {
        $sorted = [];
        $groupOrder = ['Main' => 0];

        // Sort items within each group
        foreach ($this->menus as $group => $items) {
            usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
            $sorted[$group] = $items;
        }

        // Sort groups: Main first, then others alphabetically
        uksort($sorted, function ($groupA, $groupB) use ($groupOrder) {
            $orderA = $groupOrder[$groupA] ?? 999;
            $orderB = $groupOrder[$groupB] ?? 999;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcasecmp($groupA, $groupB);
        });

        return $sorted;
    }
}

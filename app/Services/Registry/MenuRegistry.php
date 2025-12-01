<?php

namespace App\Services\Registry;

class MenuRegistry
{
    /**
     * @var array<string, array<int, array{label: string, route: string, icon: string, order: int, permission?: string}>>
     */
    private array $menus = [];

    /**
     * Register a menu item.
     *
     * @param  string  $group  Menu group name
     * @param  string  $label  Menu item label
     * @param  string  $route  Route name
     * @param  string  $icon  Icon name
     * @param  int|null  $order  Display order
     * @param  string|null  $permission  Required permission to show this menu item
     */
    public function registerItem(
        string $group,
        string $label,
        string $route,
        string $icon,
        ?int $order = null,
        ?string $permission = null
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
            'permission' => $permission,
        ];
    }

    /**
     * Get all registered menus grouped and sorted.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  User to check permissions against
     * @return array<string, array<int, array{label: string, route: string, icon: string, order: int, permission?: string}>>
     */
    public function getMenus(?\Illuminate\Contracts\Auth\Authenticatable $user = null): array
    {
        $sorted = [];
        $groupOrder = ['Main' => 0];

        foreach ($this->menus as $group => $items) {
            $filteredItems = array_filter($items, function ($item) use ($user) {
                if (empty($item['permission'])) {
                    return true;
                }

                if (! $user) {
                    return false;
                }

                return \Illuminate\Support\Facades\Gate::forUser($user)->allows($item['permission']);
            });

            usort($filteredItems, fn ($a, $b) => $a['order'] <=> $b['order']);
            $sorted[$group] = array_values($filteredItems);
        }

        $sorted = array_filter($sorted, fn ($items) => ! empty($items));

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

<?php

namespace Acun\Ui\Menu;

use Acun\Ui\Support\Icon;
use Acun\Ui\Support\Url;
use Closure;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * Turns a JSON (or array) menu definition into the tree acun:menu renders.
 *
 * Schema (resources/menu/*.json):
 *   { "menu": [
 *       { "section": "General" },
 *       { "title": "Home", "icon": "home", "route": "dashboard" },
 *       { "title": "Orders", "icon": "shopping-bag", "route": "orders.index", "active": "orders.*" },
 *       { "title": "Reports", "icon": "chart-bar", "badge": 3, "children": [
 *           { "title": "Sales", "url": "/reports/sales" },
 *           { "title": "Archive", "children": [ { "title": "2025", "url": "/reports/archive/2025" } ] }
 *       ] },
 *       { "title": "Support", "icon": "lifebuoy", "url": "https://support.example.com", "target": "_blank" }
 *   ] }
 *
 * Fields: section | title, icon (acun:icon name; an unknown name shows no icon), route (+ "params") or url,
 * active (route names or "/path" patterns; * wildcard), badge (number/text; true
 * on an item with children = number of children), target ("_blank"), children
 * (nested, up to 3 levels). url/href accept only http, https, mailto, tel and
 * relative URLs; any other scheme (javascript:, data:, …) is rendered as "#".
 * The menu does no authorization: every item in the definition is shown. To show
 * items conditionally, the application builds or filters the definition itself.
 * Groups with no children and section headings with no items below them are not shown.
 */
final class Menu
{
    /** @var array<int, array<string, mixed>>|null items(), worked out once (a layout asks for nav, mobile nav and search) */
    private ?array $items = null;

    /**
     * @param  array<int, array<string, mixed>>  $definition
     * @param  (Closure(array<string, mixed>): array<string, mixed>)|null  $transform  Applied to every item before it is resolved.
     * @param  (Closure(array<string, mixed>): ?bool)|null  $isActive  Decides whether an item is active; returning null falls back to the request.
     */
    public function __construct(
        private readonly array $definition,
        private readonly ?Closure $transform = null,
        private readonly ?Closure $isActive = null,
    ) {}

    /**
     * Builds a menu from a file path (a name under resources/menu/ or a full path), an array or a Menu.
     */
    public static function resolve(self|array|string $source): self
    {
        if ($source instanceof self) {
            return $source;
        }

        if (is_array($source)) {
            return new self($source['menu'] ?? $source);
        }

        return self::fromFile($source);
    }

    public static function fromFile(string $file): self
    {
        $path = is_file($file) ? $file : resource_path('menu/'.$file.(str_ends_with($file, '.json') ? '' : '.json'));

        if (! is_file($path)) {
            throw new InvalidArgumentException("Menu file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return new self($data['menu'] ?? $data);
    }

    /**
     * Render-ready tree: every node has type (section|item|group), title, icon, href, target, badge, active, children.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items ??= $this->removeEmptySections(
            array_values(array_filter(array_map(fn ($item) => $this->resolveItem($item, 1), $this->definition)))
        );
    }

    /**
     * Every link in the menu for acun:command-palette (with its section and parent titles).
     * Each page added to the menu shows up in search too; no separate search file is needed.
     *
     * @return array<int, array{label: string, href: string, group: string, hint: string}>
     */
    public function searchItems(): array
    {
        $result = [];
        $section = '';

        $collect = function (array $items, string $parent) use (&$collect, &$result, &$section): void {
            foreach ($items as $item) {
                if ($item['type'] === 'section') {
                    $section = $item['title'];
                } elseif ($item['type'] === 'group') {
                    $collect($item['children'], $parent === '' ? $item['title'] : $parent.' › '.$item['title']);
                } elseif ($item['href'] !== '#') {
                    $result[] = ['label' => $item['title'], 'href' => $item['href'], 'group' => $section ?: __('Menu'), 'hint' => $parent];
                }
            }
        };

        $collect($this->items(), '');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function resolveItem(array $item, int $level): ?array
    {
        if ($this->transform) {
            $item = ($this->transform)($item);
        }

        if (isset($item['section'])) {
            return $level === 1 ? ['type' => 'section', 'title' => self::text($item['section'])] : null;
        }

        if (! empty($item['children']) && $level < 3) {
            $children = array_values(array_filter(array_map(fn ($child) => $this->resolveItem($child, $level + 1), $item['children'])));

            if ($children === []) {
                return null;
            }

            return [
                'type' => 'group',
                'title' => self::text($item['title'] ?? ''),
                'icon' => $this->icon($item),
                'badge' => ($item['badge'] ?? null) === true ? count($children) : ($item['badge'] ?? null),
                'active' => collect($children)->contains('active', true),
                'children' => $children,
            ];
        }

        $href = $this->href($item);

        return [
            'type' => 'item',
            'title' => self::text($item['title'] ?? ''),
            'icon' => $this->icon($item),
            'href' => $href,
            'target' => $item['target'] ?? null,
            'badge' => $item['badge'] ?? null,
            'active' => ($this->isActive ? ($this->isActive)($item) : null) ?? $this->isActiveForRequest($item, $href),
        ];
    }

    /**
     * A title in the current language: titles are translation keys (lang/en.json can hold
     * "Siparişler": "Orders"); a title without a translation is shown as it is.
     */
    private static function text(mixed $title): string
    {
        $title = (string) $title;

        return $title === '' ? '' : (string) __($title);
    }

    /**
     * A name acun:icon knows, else null: menu data may come from a database or a tenant,
     * and one unknown name must not stop every page from rendering.
     *
     * @param  array<string, mixed>  $item
     */
    private function icon(array $item): ?string
    {
        $icon = $item['icon'] ?? null;

        return is_string($icon) && Icon::exists($icon) ? $icon : null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function href(array $item): string
    {
        if (isset($item['href'])) {
            return Url::isSafe((string) $item['href']) ? (string) $item['href'] : '#';
        }

        if (isset($item['route'])) {
            return Route::has($item['route']) ? route($item['route'], $item['params'] ?? []) : '#';
        }

        if (isset($item['url'])) {
            $url = (string) $item['url'];

            if (! Url::isSafe($url)) {
                return '#';
            }

            // A protocol-relative URL passes isSafe() only with the application's own host.
            return Url::scheme($url) !== null || Url::isProtocolRelative($url) ? $url : url($url);
        }

        return '#';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isActiveForRequest(array $item, string $href): bool
    {
        $request = request();
        $patterns = (array) ($item['active'] ?? []);

        if ($patterns === []) {
            // A route without params: any address of that route (its other params too).
            if (isset($item['route']) && empty($item['params'])) {
                return $request->routeIs($item['route']);
            }

            // Only this application's addresses can be active; external links never are.
            if ($href === '#' || ! str_starts_with($href, url('/'))) {
                return false;
            }

            return $this->isCurrentAddress($href);
        }

        foreach ($patterns as $pattern) {
            $matches = str_starts_with($pattern, '/')
                ? $request->is(ltrim($pattern, '/') ?: '/')
                : $request->routeIs($pattern);

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes section headings with no items below them (up to the next section heading).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function removeEmptySections(array $items): array
    {
        return array_values(array_filter($items, function ($item, $i) use ($items) {
            return $item['type'] !== 'section' || (isset($items[$i + 1]) && $items[$i + 1]['type'] !== 'section');
        }, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * The link is the page on screen: the same path, and every query value the link
     * has (/orders?status=open) is in the address too; other query values (a page
     * number, a search) do not matter.
     */
    private function isCurrentAddress(string $href): bool
    {
        $request = request();
        $path = fn (?string $value): string => rtrim(rawurldecode((string) $value), '/');

        if ($path(parse_url($href, PHP_URL_PATH)) !== $path(parse_url($request->url(), PHP_URL_PATH))) {
            return false;
        }

        parse_str((string) parse_url($href, PHP_URL_QUERY), $wanted);

        foreach ($wanted as $key => $value) {
            if ($request->query($key) != $value) {
                return false;
            }
        }

        return true;
    }
}

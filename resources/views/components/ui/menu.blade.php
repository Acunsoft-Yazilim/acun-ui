@props([
    // File name under resources/menu/ ('main'), a full path, an array or an Acun\Ui\Menu\Menu.
    'menu',
    // true: renders as a horizontal (top) menu; section headings are skipped.
    'horizontal' => false,
    // Horizontal menu only: true makes every section one dropdown (its groups open beside it),
    // false lists the items side by side, 'auto' groups when there are more than 6 entries.
    'sections' => 'auto',
])

{{--
    JSON menu: <acun:slot:nav><acun:menu menu="main" /></acun:slot:nav>
    See Acun\Ui\Menu\Menu for the schema and fields.
--}}
@php
    $items = \Acun\Ui\Menu\Menu::resolve($menu)->items();
    $entries = array_values(array_filter($items, fn (array $item) => $item['type'] !== 'section'));
    $groupSections = $horizontal
        && count($entries) < count($items)
        && ($sections === true || ($sections === 'auto' && count($entries) > 6));

    // Grouped horizontal menu: a section becomes one dropdown; a section with a single
    // entry shows that entry itself, entries before the first section stay as they are.
    $nodes = [];
    if ($groupSections) {
        $current = null;
        foreach ($items as $item) {
            if ($item['type'] === 'section') {
                $current = count($nodes);
                $nodes[] = ['type' => 'section', 'title' => $item['title'], 'children' => []];
            } elseif ($current === null) {
                $nodes[] = $item;
            } else {
                $nodes[$current]['children'][] = $item;
            }
        }
        $nodes = array_values(array_filter(array_map(
            fn (array $node) => $node['type'] === 'section' && count($node['children']) === 1 ? $node['children'][0] : $node,
            $nodes,
        ), fn (array $node) => $node['type'] !== 'section' || $node['children'] !== []));
    }
@endphp

@if ($groupSections)
    @foreach ($nodes as $node)
        @php($side = $loop->remaining < 2 ? 'left' : 'right')
        @if ($node['type'] === 'section')
            <acun:nav.horizontal-group :title="$node['title']" :active="collect($node['children'])->contains('active', true)" :align="$side === 'left' ? 'right' : 'left'">
                <acun:slot:icon>
                    @if ($node['children'][0]['icon'] ?? null)
                        <acun:icon :name="$node['children'][0]['icon']" size="4" />
                    @endif
                </acun:slot:icon>
                @foreach ($node['children'] as $child)
                    @if ($child['type'] === 'group')
                        <acun:nav.horizontal-sub-group :title="$child['title']" :active="$child['active']" :badge="$child['badge']" :side="$side">
                            @if ($child['icon'])
                                <acun:slot:icon><acun:icon :name="$child['icon']" size="4" /></acun:slot:icon>
                            @endif
                            @foreach ($child['children'] as $grandchild)
                                @if ($grandchild['type'] === 'group')
                                    <p class="px-3 pt-2 pb-1 font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400">{{ $grandchild['title'] }}</p>
                                    @foreach ($grandchild['children'] as $leaf)
                                        <acun:nav.sub-item compact :href="$leaf['href']" :title="$leaf['title']" :active="$leaf['active']" :target="$leaf['target'] ?? null" :badge="$leaf['badge'] ?? null" />
                                    @endforeach
                                @else
                                    <acun:nav.sub-item compact :href="$grandchild['href']" :title="$grandchild['title']" :active="$grandchild['active']" :target="$grandchild['target'] ?? null" :badge="$grandchild['badge'] ?? null" />
                                @endif
                            @endforeach
                        </acun:nav.horizontal-sub-group>
                    @else
                        <acun:nav.sub-item compact :href="$child['href']" :title="$child['title']" :active="$child['active']" :target="$child['target']" :badge="$child['badge']" />
                    @endif
                @endforeach
            </acun:nav.horizontal-group>
        @else
            @include('acun-ui::partials.menu-horizontal-entry',['item' => $node, 'align' => $side === 'left' ? 'right' : 'left'])
        @endif
    @endforeach
@else
@foreach ($items as $item)
    @if ($item['type'] === 'section')
        @unless ($horizontal)
            <acun:nav.heading :first="$loop->first">{{ $item['title'] }}</acun:nav.heading>
        @endunless
    @elseif ($item['type'] === 'group')
        @if ($horizontal)
            <acun:nav.horizontal-group :title="$item['title']" :active="$item['active']" :badge="$item['badge']" :align="$loop->remaining < 2 ? 'right' : 'left'">
                <acun:slot:icon>
                    @if ($item['icon'])
                        <acun:icon :name="$item['icon']" size="4" />
                    @endif
                </acun:slot:icon>
                @foreach ($item['children'] as $child)
                    @if ($child['type'] === 'group')
                        <p class="px-3 pt-2 pb-1 font-mono text-2xs font-medium tracking-[0.08em] text-gray-500 uppercase dark:text-zinc-400">{{ $child['title'] }}</p>
                        @foreach ($child['children'] as $grandchild)
                            <acun:nav.sub-item compact :href="$grandchild['href']" :title="$grandchild['title']" :active="$grandchild['active']" :target="$grandchild['target'] ?? null" :badge="$grandchild['badge'] ?? null" />
                        @endforeach
                    @else
                        <acun:nav.sub-item compact :href="$child['href']" :title="$child['title']" :active="$child['active']" :target="$child['target']" :badge="$child['badge']" />
                    @endif
                @endforeach
            </acun:nav.horizontal-group>
        @else
            <acun:nav.group :title="$item['title']" :active="$item['active']" :badge="$item['badge']">
                <acun:slot:icon>
                    @if ($item['icon'])
                        <acun:icon :name="$item['icon']" />
                    @endif
                </acun:slot:icon>
                @foreach ($item['children'] as $child)
                    @if ($child['type'] === 'group')
                        <acun:nav.sub-group :title="$child['title']" :active="$child['active']" :badge="$child['badge'] ?? null">
                            @foreach ($child['children'] as $grandchild)
                                <acun:nav.sub-item :href="$grandchild['href']" :title="$grandchild['title']" :active="$grandchild['active']" :target="$grandchild['target'] ?? null" :badge="$grandchild['badge'] ?? null" />
                            @endforeach
                        </acun:nav.sub-group>
                    @else
                        <acun:nav.sub-item :href="$child['href']" :title="$child['title']" :active="$child['active']" :target="$child['target']" :badge="$child['badge']" />
                    @endif
                @endforeach
            </acun:nav.group>
        @endif
    @else
        @if ($horizontal)
            <acun:nav.horizontal-item :href="$item['href']" :title="$item['title']" :active="$item['active']" :target="$item['target']" :badge="$item['badge']">
                <acun:slot:icon>
                    @if ($item['icon'])
                        <acun:icon :name="$item['icon']" size="4" />
                    @endif
                </acun:slot:icon>
            </acun:nav.horizontal-item>
        @else
            <acun:nav.item :href="$item['href']" :title="$item['title']" :active="$item['active']" :badge="$item['badge']" :target="$item['target']">
                <acun:slot:icon>
                    @if ($item['icon'])
                        <acun:icon :name="$item['icon']" />
                    @endif
                </acun:slot:icon>
            </acun:nav.item>
        @endif
    @endif
@endforeach
@endif

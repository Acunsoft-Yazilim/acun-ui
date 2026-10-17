{{-- One top-level entry of the horizontal acun:menu: a link, or a group whose links open below it. --}}
@if ($item['type'] === 'group')
    <acun:nav.horizontal-group :title="$item['title']" :active="$item['active']" :badge="$item['badge']" :align="$align">
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
    <acun:nav.horizontal-item :href="$item['href']" :title="$item['title']" :active="$item['active']" :target="$item['target']" :badge="$item['badge']">
        <acun:slot:icon>
            @if ($item['icon'])
                <acun:icon :name="$item['icon']" size="4" />
            @endif
        </acun:slot:icon>
    </acun:nav.horizontal-item>
@endif

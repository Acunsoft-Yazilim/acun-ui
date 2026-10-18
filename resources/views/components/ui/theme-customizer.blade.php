@props([
    // The menu's sections (collapse, dark menu, top bar mode); false in the horizontal layout.
    'sidebar' => true,
    'title' => null,
])
{{-- 1.x: the panel's appearance customizer. A wrapper of acun:theme-switcher (as="drawer") until 3.0; the skin, width, sidebar and navbar sections are acunsoft/acun-pro's. --}}
<acun:theme-switcher as="drawer" name="theme-customizer" :title="$title" :sections="['mode', 'color', 'theme', 'skin', 'width', ...($sidebar ? ['sidebar', 'navbar'] : [])]" {{ $attributes }} />

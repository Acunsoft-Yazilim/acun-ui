{{--
    Renders buttons in a group where they sit flush against each other and
    look like a single unit. Each button should also get these classes:
    `!rounded-none first:!rounded-l last:!rounded-r -ml-px first:ml-0 relative hover:z-10 focus-visible:z-10`
--}}
<div {{ $attributes->merge(['class' => 'inline-flex items-stretch']) }} role="group">
    {{ $slot }}
</div>

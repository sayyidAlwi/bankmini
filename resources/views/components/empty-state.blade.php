@props([
    'title',
    'description' => null,
    'icon' => '•',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="empty-state__icon" aria-hidden="true">{{ $icon }}</span>
    <p class="empty-state__title">{{ $title }}</p>

    @if ($description)
        <p class="empty-state__desc">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>

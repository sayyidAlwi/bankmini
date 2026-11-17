@props([
    'label',
    'value' => '—',
    'hint' => null,
    'tone' => 'green',
])

<article {{ $attributes->merge(['class' => 'stat-card stat-card--'.$tone]) }}>
    <p class="stat-card__label">{{ $label }}</p>
    <p class="stat-card__value">{{ $value }}</p>

    @if ($hint)
        <p class="stat-card__hint">{{ $hint }}</p>
    @endif
</article>

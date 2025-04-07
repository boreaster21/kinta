@props([
    'for' => null,
])

<label {{ $attributes->merge(['class' => 'c-label', 'for' => $for]) }}>
    {{ $slot }}
</label> 
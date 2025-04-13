@props([
    'as' => 'button', // Can be 'button' or 'a'
    'type' => 'button', // Default type for <button>
    'href' => null,
    'variant' => 'primary', // e.g., primary, secondary, danger, link, admin-primary, admin-secondary
    'size' => null, // e.g., sm, lg
    'disabled' => false,
])

@php
$baseClass = 'c-button';
$variantClasses = [
    'primary' => 'c-button--primary',
    'secondary' => 'c-button--secondary',
    'danger' => 'c-button--danger',
    'link' => 'c-button--link',
    'admin-primary' => 'c-button--admin-primary',
    'admin-secondary' => 'c-button--admin-secondary',
];
$sizeClasses = [
    'sm' => 'c-button--sm',
    'lg' => 'c-button--lg',
];

$variantClass = $variantClasses[$variant] ?? '';
$sizeClass = $size ? ($sizeClasses[$size] ?? '') : '';

$finalAttributes = $attributes->merge([
    'class' => trim($baseClass . ' ' . $variantClass . ' ' . $sizeClass)
]);

@endphp

@if ($as === 'a')
    <a href="{{ $href }}" {{ $finalAttributes }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $finalAttributes }}>
        {{ $slot }}
    </button>
@endif
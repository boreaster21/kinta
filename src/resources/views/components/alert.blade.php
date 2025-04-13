@props([
    'type' => 'info', // default type
    'message' => null,
])

@php
$baseClass = 'c-alert';
$typeClasses = [
    'success' => 'c-alert--success',
    'danger' => 'c-alert--danger',
    'warning' => 'c-alert--warning',
    'info' => 'c-alert--info', // 必要ならinfoスタイルをCSSに追加
];
$typeClass = $typeClasses[$type] ?? $typeClasses['info'];
@endphp

@if ($message || !$slot->isEmpty())
<div {{ $attributes->merge(['class' => $baseClass . ' ' . $typeClass]) }}>
    {{ $message ?? $slot }}
</div>
@endif
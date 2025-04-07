@props([
    'name',
    'value' => null,
    'disabled' => false,
    'required' => false,
])

@php
$errorClass = $errors->has($name) ? 'is-error' : '';
$value = old($name, $value);
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $name }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'c-textarea ' . $errorClass]) }}
>{{ $value }}</textarea> 
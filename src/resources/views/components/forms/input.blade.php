@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'disabled' => false,
    'required' => false,
])

@php
$errorClass = $errors->has(str_replace(['[', ']'], ['.', ''], $name)) ? 'is-error' : '';
$value = old(str_replace(['[', ']'], ['.', ''], $name), $value);
$inputId = $id ?? $name;
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $inputId }}"
    value="{{ $value }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $attributes->merge(['class' => 'c-input ' . $errorClass]) }}
>
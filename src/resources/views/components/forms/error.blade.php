@props([
    'field',
])

@error($field)
    <div {{ $attributes->merge(['class' => 'c-error-message']) }}>
        {{ $message }}
    </div>
@enderror 
@props(['value'])

<textarea {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {!! nl2br(e($value ?? $slot)) !!}
</textarea>

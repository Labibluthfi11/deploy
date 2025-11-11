@props([
    'label',
    'value',
    'color' => 'gray', // bisa: green, yellow, red, blue, etc
])

@php
    $colors = [
        'green' => ['bg' => 'bg-green-200', 'text' => 'text-green-800'],
        'yellow' => ['bg' => 'bg-yellow-200', 'text' => 'text-yellow-800'],
        'red' => ['bg' => 'bg-red-200', 'text' => 'text-red-800'],
        'blue' => ['bg' => 'bg-blue-200', 'text' => 'text-blue-800'],
        'gray' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-800'],
    ];
    $selected = $colors[$color] ?? $colors['gray'];
@endphp

<div class="{{ $selected['bg'] }} p-4 rounded-lg shadow text-center animate-fade-in-up">
    <p class="text-sm {{ $selected['text'] }}">{{ $label }}</p>
    <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
</div>

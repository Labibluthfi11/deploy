@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-softIndigo-500 focus:border-softIndigo-500 transition-all duration-200 ease-in-out'
]) !!}>

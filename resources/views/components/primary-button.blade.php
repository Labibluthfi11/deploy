@props(['type' => 'submit'])

<button {{ $attributes->merge([
    'type' => $type,
    'class' => 'w-full justify-center py-3 bg-softIndigo-600 hover:bg-softIndigo-700 active:bg-softIndigo-800 focus:ring-2 focus:ring-softIndigo-500 focus:ring-offset-2 focus:ring-offset-dustyLatte-100 text-black font-semibold text-lg rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 hover:shadow-xl ease-out'
]) }}>
    {{ $slot }}
</button>

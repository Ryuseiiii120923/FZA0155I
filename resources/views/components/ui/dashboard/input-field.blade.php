@props([
    'id',
    'label',
    'type' => 'text',
])

<div class="flex-col w-full ">
    <label for="{{ $id }}" class="block text-sm font-medium text-black">
        {{ $label }}
    </label>

    <input
        type="{{ $type }}"
        id="{{ $id }}"
        {{ $attributes->merge([
            'class' => 'mt-1 block w-full border border-black rounded-md px-2 py-1'
        ]) }}
    >
</div>
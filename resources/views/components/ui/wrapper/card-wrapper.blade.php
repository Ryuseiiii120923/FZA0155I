@props(['readonly' => false])
<div class="bg-white px-3 py-4 rounded-lg w-full @if($readonly) opacity-50 cursor-not-allowed @endif">
    <div class="bg-white shadow-md w-full mx-auto px-3 py-4 rounded-lg h-[500px] flex flex-col">
        {{ $slot }}
    </div>
</div>
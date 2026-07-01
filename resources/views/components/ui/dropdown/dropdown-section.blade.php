<div x-data="{ showFixed: false }"
    @scroll.window="showFixed = window.scrollY > 120">
    @unless ($readonly)
    <div class="px-5 py-4 flex gap-3 bg-white shadow-sm">
        <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
            + Add HF
        </button>
    </div>

    <div x-show="showFixed" x-transition
        class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg px-5 py-4 flex flex-wrap gap-3">
        <div class="flex gap-3 flex-wrap">
            <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">+ Add Hf</button>
        </div>
    </div>
    @endunless

</div>

@include('components.ui.dropdown.dropdown-content')
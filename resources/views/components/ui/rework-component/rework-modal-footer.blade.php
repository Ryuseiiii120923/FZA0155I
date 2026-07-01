<div class="flex items-center justify-end gap-3 p-4 md:p-5 border-t border-gray-200">
    <button type="button"
        @if($readonly) disabled @endif
        wire:click="cancelReworkModal"
        @click="openAddRework = false"
        class="px-5 py-2.5 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-100">
        Cancel
    </button>
    <button type="button"
        @if($readonly) disabled @endif
        wire:click="confirmReworks"
        x-on:rework-confirmed.window="openAddRework = false"
        class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-[#0F3C89] hover:bg-blue-800">
        Confirm
    </button>
</div>
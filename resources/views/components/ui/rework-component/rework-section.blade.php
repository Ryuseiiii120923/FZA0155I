<div x-data="{ openAddRework: false }" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($readonly) opacity-50 cursor-not-allowed @endif">
    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold text-center text-white p-4">Rework</p>
    </div>
     @unless($readonly)
    <div class="w-full flex flex-col items-center mt-5">
        <button @click="$wire.reviewCommittedReworks().then(() => { openAddRework = true; $nextTick(() => $refs.firstInput?.focus()); })"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            type="button" id="add-rework" @if($readonly) disabled @endif>
            Add Rework / Edit Staged Rework
        </button>
    </div>
     @endunless

    @include('components.ui.rework-component.rework-table')

    @error('currentHfno')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('totalInsp')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newType')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newQuan')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror

    <!-- Add/Edit Rework Modal -->
   

    <div x-show="openAddRework" x-transition x-cloak x-trap.noscroll="openAddRework"
        class="fixed top-0 right-0 left-0 z-50 justify-center items-center flex w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
        style="display: none">
         <div x-show="openAddRework" x-transition.opacity  class="fixed inset-0 bg-white/10 backdrop-blur-sm z-40"></div>
        @include('components.ui.rework-component.rework-modal-panel')
    </div>
</div>
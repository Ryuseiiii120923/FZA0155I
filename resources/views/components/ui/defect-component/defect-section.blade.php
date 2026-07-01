<div x-data="{ openAddDefect: false }" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4  @if($readonly) opacity-50 cursor-not-allowed @endif">

    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold text-center text-white p-4">Defect</p>
    </div>

    <!-- ADD DEFECT BUTTON -->
    @unless($readonly)
    <div class="w-full flex justify-center mb-3 px-3 mt-5">
        <button
            @click="$wire.reviewCommittedDefects().then(() => { openAddDefect = true })"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            id="add-defect">
            Add Defect / Edit Staged Defects
        </button>
    </div>
    @endunless
    @include('components.ui.defect-component.defect-table')

    <!-- ERROR MESSAGES -->
    @error('newDefect') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

    @include('components.ui.defect-component.defect-modal')

</div>
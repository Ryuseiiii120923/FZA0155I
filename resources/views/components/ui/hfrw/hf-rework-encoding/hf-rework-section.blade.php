<div x-data="{ open: false }"> <!-- sync Alpine with Livewire -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-white">Hand Finishing Rework Encoding</h2>
        <p class="text-sm text-gray-500 mt-1">Record and encode hand finishing rework data</p>
    </div>
    @if (session()->has('success'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
            <p class="text-gray-700 mb-4">{{ session('success') }}</p>

            <button
                @click="open = false;
            location.reload();
            "
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif

    <!-- HEADER -->
    <div class="bg-gray-700 w-full ">
        <p class="text-4xl font-extrabold text-center text-white p-4 ">For Rework</p>
    </div>

    <!-- TABLE -->
    @include('components.ui.hfrw.hf-rework-encoding.encoding-table')
   
    <!-- MODAL -->

    <!-- components.ui.hfrw.defect.defect-modal -->
    @include('components.ui.hfrw.hf-rework-encoding.encoding-modal')
</div>
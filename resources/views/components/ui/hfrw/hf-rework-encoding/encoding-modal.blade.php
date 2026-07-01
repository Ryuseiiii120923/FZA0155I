    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
        class="fixed inset-0 flex justify-center bg-black/50 z-50 py-6">

        <!-- MODAL -->
        <div class="bg-white w-full max-w-6xl h-full max-h-[90vh] flex flex-col rounded-lg shadow-lg overflow-hidden">

            <!-- HEADER -->
            <div class="bg-gray-700 shrink-0">
                <p class="text-4xl font-extrabold text-center text-white p-4">
                    HF Rework
                </p>
            </div>

            <!-- BUTTON -->
            <div class="shrink-0 px-5 py-4 flex gap-3 bg-white shadow-md">
                <button wire:click="addNew"
                    class="bg-green-600 text-white px-4 py-2 rounded-md">
                    + Add Worker
                </button>
                <button
                    @click="open = false"
                    wire:click="removeSelectedPPF"
                    class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Cancel
                </button>
                <button
                    @click="open = false"
                    wire:click="saveHFRework()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    Save
                </button>
            </div>


            <!-- 🔥 SCROLL AREA -->
            <div class="overflow-y-auto px-5 py-4 space-y-4">

                @foreach($forms as $formId => $form)
                @if(($form['ppfno'] ?? null) == $selectedPPF)
                <div wire:key="worker-form-{{ $formId }}"
                    class="border rounded shadow p-3"
                    x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

                    <!-- HEADER -->
                    <div class="flex justify-between items-center cursor-pointer px-4 py-2 bg-gray-100"
                        @click="open = !open; $wire.toggle('{{ $formId }}')">

                        <span>PPF #: {{ $form['ppfno'] ?? 'New' }}</span>
                        <span>Status: {{ $form['status'] ?? 'Pending' }}</span>

                        <div class="flex justify-end gap-2">
                            <button
                                @click.stop
                                wire:click="editHF('{{ $formId }}')"
                                class="rounded px-3 py-1 bg-blue-600 text-white text-sm">
                                Edit
                            </button>

                            <button @click.stop wire:click="remove('{{ $formId }}')"
                                class="bg-red-600 text-white px-3 py-1 rounded">
                                Remove
                            </button>
                        </div>


                    </div>

                    <!-- BODY -->
                    @include('components.ui.hfrw.hf-rework-encoding.modal-body')

                </div>
                @endif
                @endforeach

            </div>

        </div>
    </div>
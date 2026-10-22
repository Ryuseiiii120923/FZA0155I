<div
    wire:key="worker-form-{{ $formId }}"
    class="{{ $isSub ? 'ml-6 border-l-4 border-blue-300 pl-4 mt-4' : 'border rounded shadow p-3' }} relative"
    x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

    {{-- Header --}}
    <div class="flex justify-between items-center cursor-pointer px-4 py-2"
        @click="open = !open; $wire.toggle('{{ $formId }}')">

        <span class="font-medium">
            @if($isSub)
            Sub Process #{{ $subIndex ?? '' }} — HF #{{ $form['hf_id'] ?: 'Unknown' }}
            @else
            Worker Form #{{ $form['hf_id'] ?? 'Unknown' }}
            @endif
        </span>
        <span class="font-medium">Date Created: {{ $form['created_at'] ?? now()->format('Y-m-d') }}</span>
        <span class="font-medium">Date Updated: {{ $form['updated_at'] ?? 'Not Yet Updated' }}</span>

        @unless($readonly)
        <div class="flex items-center gap-2">
            <button
                @click.stop
                wire:click="openEditModal('{{ $formId }}')"
                class="rounded px-3 py-1 bg-blue-600 text-white text-sm">
                Edit
            </button>
            <button
                @click.stop
                @click.prevent="if (confirm(@js($isSub
                    ? 'Are you sure you want to remove this sub process?'
                    : 'Are you sure you want to remove this form? Its sub processes will be removed too.')))
                    $wire.remove(@js($formId))"
                class="rounded px-3 py-1 bg-red-600 text-white text-sm">
                Remove
            </button>
            <svg class="w-5 h-5 transform"
                :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        @endunless
    </div>

    {{-- Dropdown content --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
        class="overflow-hidden">

        <div class="flex flex-col gap-4 mt-4 p-4 bg-gray-50 rounded">

            <div class="flex flex-col sm:flex-row gap-4">
                <div class="w-full sm:w-1/2">
                    <label class="block text-sm font-medium">Finishing Procedure</label>
                    <div class="flex items-center gap-3">
                        <input type="text"
                            wire:model="forms.{{ $formId }}.finishingProcedure"
                            class="w-full border bg-gray-500 p-2 rounded"
                            readonly>
                    </div>
                </div>

                <div class="w-full sm:w-1/2">
                    <label class="block text-sm font-medium">HF ID</label>
                    <div class="flex items-center gap-3">
                        <input type="text"
                            wire:model="forms.{{ $formId }}.hf_id"
                            class="w-full border bg-gray-500 p-2 rounded"
                            readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}">
                        @if(!empty($form['hf_name']))
                        <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p>
                        @endif
                    </div>
                </div>

                @error('forms.' . $formId . '.hf_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror

                <div>
                    <label class="block text-sm font-medium">
                        Total Inspect @if($isSub)<span class="text-xs text-gray-500">(Good Qty of main process)</span>@endif
                    </label>
                    <input type="number"
                        wire:model="total_inspect"
                        @if($isSub) readonly @endif
                        class="w-full border p-2 rounded {{ $isSub ? 'bg-gray-200' : '' }}"
                        placeholder="Enter Total Inspect">
                    @error('forms.' . $formId . '.total_inspect')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Modal (same one for process and sub process) --}}
            <div
                x-data="{ open: @entangle('modalOpen.' . $formId) }"
                x-show="open"
                x-cloak
                class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
                @keydown.escape.window="open = false">
                <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
                    <div class="flex flex-col gap-4">

                        <h3 class="text-base font-semibold">
                            {{ $isSub ? 'Sub Process' : 'Process' }}
                            {{ ($modalMode[$formId] ?? 'add') === 'edit' ? '— Edit' : '— Add' }}
                        </h3>

                        <div class="w-full mx-auto">
                            <label for="finishingMachine-{{ $formId }}" class="block text-sm font-medium text-gray-700">
                                Finishing Procedure
                            </label>
                            <select id="finishingMachine-{{ $formId }}"
                                class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                                wire:model="finishingProcedure"
                                required>
                                <option value="">--- Select Finishing Procedure ---</option>
                                <option value="Hand Finishing">Hand Finishing</option>
                                <option value="Cold Deflushing">Cold Deflashing</option>
                                <option value="Milling">Milling</option>
                                <option value="Post Curing">Post Curing</option>
                                <option value="Cutting">Cutting</option>
                                <option value="Punching">Punching</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">HF ID</label>
                            @if(!empty($form['hf_name']))
                            <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p>
                            @endif
                            <input type="text"
                                wire:model.lazy="hf_id"
                                wire:blur="checkHf('{{ $formId }}')"
                                class="w-full border p-2 rounded"
                                placeholder="Enter HF ID"
                                maxlength="4" pattern="\d{4}"
                                oninput="this.value = this.value.toUpperCase()">
                            @error('forms.' . $formId . '.hf_id')
                            <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Total Inspect</label>
                            <input type="number"
                                wire:model="total_inspect"
                                class="w-full border p-2 rounded"
                                placeholder="Enter Total Inspect">
                            @error('forms.' . $formId . '.total_inspect')
                            <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            wire:click="saveHF('{{ $formId }}')"
                            wire:loading.attr="disabled"
                            wire:target="saveHF"
                            @if(!empty($hasErrorForm[$formId])) disabled @endif
                            class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full disabled:opacity-50">
                            Save
                        </button>

                        <button
                            wire:click="cancelHF('{{ $formId }}')"
                            class="bg-gray-500 text-white px-4 py-2 rounded mt-2 w-full">
                            Exit
                        </button>
                    </div>
                </div>
            </div>

            {{-- Defects + Rework (reused as-is) --}}
            <div class="flex flex-col sm:flex-row gap-6 mt-4">
                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:partials::defects.defects
                        :formId="$formId"
                        :loadedDefects="$form['defects']"
                        :loadedSmallDefects="$form['smallDefects']"
                        :dispatchPrefix="'operator'"
                        :readonly="$readonly"
                        :key="'defects-'.$formId" />
                </div>
                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:partials::reworks.reworks
                        :formId="$formId"
                        :loadedRework="$form['rework']"
                        :hfNo="$form['hf_id'] ?? ''"
                        :totalInsp="(int) ($form['total_inspect'] ?? 0)"
                        :dispatchPrefix="'operator'"
                        :readonly="$readonly"
                        :key="'reworks-'.$formId" />
                </div>
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium">Total Good Qty.</label>
                <input type="text"
                    wire:model="forms.{{ $formId }}.GoodQty"
                    class="w-full border p-2 rounded"
                    readonly>
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium">Remarks</label>
                <div x-data="{
                        saveTimer: null,
                        buttonTimer: null,
                        typing() {
                            window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: true } }));
                            clearTimeout(this.saveTimer);
                            clearTimeout(this.buttonTimer);
                            this.saveTimer = setTimeout(() => {
                                $wire.saveRemarks('{{ $formId }}');
                            }, 500);
                            this.buttonTimer = setTimeout(() => {
                                window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: false } }));
                            }, 1000);
                        }
                    }">
                    <input
                        type="text"
                        wire:model="forms.{{ $formId }}.Remarks"
                        @input="typing()"
                        class="w-full border p-2 rounded">
                </div>
            </div>

            {{-- Sub processes (main forms only) --}}
            @unless($isSub)
            @php
            $children = collect($forms)->filter(
            fn ($f) => ($f['parent_form_id'] ?? null) === $formId
            );
            @endphp

            @foreach($children as $childId => $child)
            <hr class="border-t-2 border-dashed border-blue-300 my-2">
            @include('components.ui.dropdown.dropdown-form', [
            'formId' => $childId,
            'form' => $child,
            'isSub' => true,
            'subIndex' => $loop->iteration,
            ])
            @endforeach

            @unless($readonly)
            <div class="pt-2">
                <button type="button"
                    wire:click="addSubProcess('{{ $formId }}')"
                    wire:loading.attr="disabled"
                    wire:target="addSubProcess"
                    class="bg-blue-100 text-blue-700 border border-blue-300 px-4 py-2 rounded-md text-sm hover:bg-blue-200 disabled:opacity-50">
                    + Add Sub Process
                </button>
            </div>
            @endunless
            @endunless

        </div>
    </div>
</div>
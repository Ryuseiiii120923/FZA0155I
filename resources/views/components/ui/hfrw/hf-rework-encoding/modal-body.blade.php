      <div x-show="open"
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 max-h-0"
          x-transition:enter-end="opacity-100 max-h-screen"
          x-transition:leave="transition ease-in duration-300"
          x-transition:leave-start="opacity-100 max-h-screen"
          x-transition:leave-end="opacity-0 max-h-0"
          class="overflow-hidden">

          <div class="flex flex-col gap-4 p-4 bg-gray-50 rounded">

              <div class="flex flex-col sm:flex-row gap-4">
                  <div class="w-full sm:w-1/2">
                      <label class="block text-sm font-medium">HF ID</label>
                      <div class="flex items-center gap-3">
                          <input type="text" wire:model="forms.{{ $formId }}.hf_id" class="w-full border bg-gray-500 p-2 rounded" readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}">
                          @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                      </div>
                  </div>
                  @error('forms.' . $formId . '.hf_id')
                  <p class="text-red-500 text-sm">{{ $message }}</p>
                  @enderror
                  <div class="w-full sm:w-1/2">
                      <label class="block text-sm font-medium">Total Inspect</label>
                      <input type="number" readonly wire:model="forms.{{ $formId }}.total_inspect" class="w-full border bg-gray-500 p-2 rounded" placeholder="Enter Total Inspect">
                  </div>
              </div>

              <div
                  x-data="{ open: @entangle('modalOpen.' . $formId) }"
                  x-show="open"
                  x-cloak
                  class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
                  @keydown.escape.window="open = false">
                  <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
                      <div class="flex flex-col gap-4">
                          <div>
                              <label class="block text-sm font-medium">HF ID</label>
                              @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                              <input type="number"
                                  wire:model.lazy="forms.{{ $formId }}.hf_id"
                                  wire:blur="checkHf('{{ $formId }}')"
                                  class="w-full border p-2 rounded"
                                  placeholder="Enter HF ID"
                                  maxlength="4" pattern="\d{4}">

                              @error('forms.' . $formId . '.hf_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                          </div>

                          <div>
                              <label class="block text-sm font-medium">Total Inspect</label>
                              <input type="number"
                                  wire:model="forms.{{ $formId }}.total_inspect"
                                  class="w-full border p-2 rounded"
                                  placeholder="Enter Total Inspect">
                              @error('forms.' . $formId . '.total_inspect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                          </div>

                          <button wire:click="saveHF('{{ $formId }}')" @if(!empty($hasErrorForm[$formId])) disabled @endif class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                              Save
                          </button>

                          <button
                              wire:click="CloseModal('{{ $formId }}')"
                              class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                              Exit
                          </button>
                      </div>
                  </div>
              </div>

              <div class="flex gap-6">
                  <livewire:partials::defects.defects
                      :formId="$formId"
                      :loadedDefects="$form['defects']"
                      :loadedSmallDefects="$form['smallDefects']"
                      :dispatchPrefix="'hfrw'"
                      :key="'defects-'.$formId" />
              </div>
          </div>

      </div>
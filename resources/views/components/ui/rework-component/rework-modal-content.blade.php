<div class="flex flex-1 justify-center gap-6 px-6 py-4">
    <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
        <!-- HF No. + Total Insp (readonly, pre-filled) -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-black mb-1">HF No.</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly x-ref="firstInput"
                        class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                        wire:blur="checkHf"
                        wire:model="currentHfno">
                    @if(!empty($currentHfno))
                    <span class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $currentHfname }}</span>
                    @endif
                </div>
                @error('currentHfno') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-black mb-1">Total Insp Qty.</label>
                <input type="number" readonly
                    class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                    wire:model="totalInsp">
                @error('totalInsp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Staged entries list -->
        @if(count($staged) > 0)
        <div class="border border-blue-200 bg-blue-50 rounded-lg px-4 py-3 space-y-2">
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-widest mb-1">Staged (ready to confirm)</p>
            @foreach($staged as $si => $stagedRow)
            <div class="flex items-center justify-between gap-2 bg-white border border-blue-100 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="text-sm font-semibold text-gray-800 truncate">{{ $stagedRow['type'] }}</span>
                    <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full shrink-0">qty: {{ $stagedRow['qty'] }}</span>
                </div>
                <button type="button"
                    wire:click="removeStagedRework({{ $si }})"
                    class="text-red-500 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition shrink-0">
                    ✕
                </button>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
            {{ count($staged) > 0 ? 'Add Another Entry' : 'Rework Entry' }}
        </p>

        <!-- Defect type selector -->
        <div>
            <label class="block text-sm font-medium text-black mb-1">Rework Defect</label>
            <select wire:model="newType"
                class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                <option value="">-- Select Rework --</option>
                @foreach ($this->reworkMasterList as $r)
                @php
                $alreadyStaged = collect($staged)->contains('type', $r->DefectType);
                $alreadyCommitted = collect($committed)->contains(fn($rw) => ($rw['hfno'] ?? '') === $currentHfno && ($rw['type'] ?? '') === $r->DefectType);
                @endphp
                <option value="{{ $r->DefectType }}"
                    @if($alreadyStaged || $alreadyCommitted) disabled @endif>
                    {{ $r->DefectType }}{{ ($alreadyStaged || $alreadyCommitted) ? ' (added)' : '' }}
                </option>
                @endforeach
            </select>
            @error('newType') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Quantity -->
        <div>
            <label class="block text-sm font-medium text-black mb-1">Quantity</label>
            <input type="number" min="1"
                class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                wire:model="newQuan">
            @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Stage button -->
        <button type="button"
            wire:click="stageRework"
            class="w-full px-4 py-2 rounded-lg border-2 border-blue-500 text-blue-600 text-sm font-semibold hover:bg-blue-50 transition">
            + Stage This Entry
        </button>
    </div>
</div>
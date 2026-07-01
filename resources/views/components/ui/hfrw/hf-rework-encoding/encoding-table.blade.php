    <div class="overflow-x-auto mt-3">
        <div class="w-full flex justify-end mb-3">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search PPF No..."
                class="border border-gray-400 rounded px-3 py-2 text-black w-full sm:w-64">
        </div>
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2  text-center">PPFNO</th>
                    <th class="px-4 py-2  text-center">Rework No</th>
                    <th class="px-4 py-2  text-center">Total Rework</th>
                    <th class="px-4 py-2 text-center">Action</th>
                    <th class="px-4 py-2  text-center">Status</th>
                </tr>
            </thead>

            <tbody class="bg-gray-700">
                @forelse ($this->pendingRework as $data )
                <tr wire:key="rework-{{ $data['ppfno'] }}">
                    <td class="px-4 py-2  text-center">{{ (int) $data['ppfno'] ?? '' }}</td>
                    <td class="px-4 py-2  text-center">{{ (int) $data['reworkNo'] ?? 0 }}</td>
                    <td class="px-4 py-2 text-center">{{ $data['qty'] ?? '' }}</td>
                    <td class=" py-2 flex justify-center gap-2">

                        <button
                            class="text-white bg-green-700 px-4 py-2 rounded  @if (($data['status'] ?? '') == 'Confirmed') opacity-50  @endif"
                            @if (($data['status'] ?? '' )=='Confirmed' ) disabled @endif
                            @click="open = true; $wire.confirmPPF('{{ $data['ppfno'] }}', '{{ $data['reworkNo'] }}')">
                            Confirm
                        </button>
                        <button
                            class="text-white bg-blue-700 px-4 py-2 rounded"
                            @if ($data['status']==='Pending' )
                            disabled
                            :class="{ 'opacity-50 cursor-not-allowed': true }"
                            @endif
                            @click="open = true; $wire.edit('{{ $data['ppfno'] }}', '{{ $data['reworkNo'] }}')">
                            Edit
                        </button>
                        <button
                            class="bg-red-500 text-white px-3 py-1 rounded"
                            @if ($data['status']==='Pending' )
                            disabled
                            :class="{ 'opacity-50 cursor-not-allowed': true }"
                            @endif
                            @click.prevent="if (confirm('Are you sure you want to delete this record?')) $wire.delete(@js($data['ppfno']), @js($data['reworkNo']))">
                            Delete
                        </button>
                    </td>
                    <td class="px-4 py-2 text-center">{{ $data['status'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
         <div class="w-full mt-3">
        {{ $this->pendingRework->links() }}
    </div>
    </div>
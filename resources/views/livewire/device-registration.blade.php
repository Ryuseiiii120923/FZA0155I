
<div x-data="deviceRegistration()" x-init="init()">
    @if ($showRegistrationPrompt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900">New Tablet Detected</h3>
                <p class="mt-2 text-sm text-gray-500">
                    This device hasn't been registered yet. Enter a name for it to continue.
                </p>

                <form wire:submit="submitHostName" class="mt-4 space-y-3">
                    <div>
                        <input
                            type="text"
                            wire:model="hostNameInput"
                            placeholder="e.g. LINE3-TABLET02"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            autofocus
                        >
                        @error('hostNameInput')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submitHostName"
                        class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="submitHostName">Register Device</span>
                        <span wire:loading wire:target="submitHostName">Saving...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

@once
        <script>
            function deviceRegistration() {
                return {
                    /**
                     * Reads device_uuid from localStorage, generating and
                     * persisting one with crypto.randomUUID() the first
                     * time this tablet ever opens the app. Never generates
                     * a second UUID unless localStorage has been cleared.
                     */
                    getOrCreateDeviceUuid() {
                        const STORAGE_KEY = 'device_uuid';
                        let uuid = localStorage.getItem(STORAGE_KEY);

                        if (!uuid) {
                            uuid = crypto.randomUUID();
                            localStorage.setItem(STORAGE_KEY, uuid);
                        }

                        return uuid;
                    },

                    init() {
                        const uuid = this.getOrCreateDeviceUuid();

                        this.$wire.registerDevice(uuid).catch((error) => {
                            console.error('Device registration check failed:', error);
                        });
                    },
                };
            }
        </script>
@endonce
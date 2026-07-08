<div x-data="{ showFixed: false }"
    @scroll.window="showFixed = window.scrollY > 120"
    x-init="
        $wire.on('scroll-to-form', ({ formId }) => {
            $nextTick(() => {
                setTimeout(() => {
                    const el = document.querySelector('[wire\\:key=\'worker-form-' + formId + '\']');
                    if (!el) return;

                    const container = document.getElementById('prencode-scroll-container');
                    if (container) {
                        const containerRect = container.getBoundingClientRect();
                        const elRect = el.getBoundingClientRect();
                        container.scrollTo({
                            top: container.scrollTop + elRect.top - containerRect.top - 20,
                            behavior: 'smooth'
                        });
                    } else {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 150);
            });
        });
    ">
    @unless ($readonly)
    <div class="px-5 py-4 flex gap-3 bg-white shadow-sm">
        <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
            + Add HF
        </button>
    </div>

    <div x-show="showFixed" x-transition
        class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg px-5 py-4 flex flex-wrap gap-3">
        <div class="flex gap-3 flex-wrap">
            <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">+ Add Hf</button>
        </div>
    </div>
    @endunless

</div>

@include('components.ui.dropdown.dropdown-content')
<div class="w-full mx-auto relative"
    x-data="{
        open: false,
        selected: null,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        view: 'days',
        yearRangeStart: new Date().getFullYear() - 6,
        hfDateWire: @entangle('hfDate'),

        initFromWire() {
            const val = this.hfDateWire;
            if (val) {
                const parts = val.split('-').map(Number);
                this.selected = new Date(parts[0], parts[1] - 1, parts[2]);
                this.viewYear = parts[0];
                this.viewMonth = parts[1] - 1;
            } else {
                this.selected = null;
            }
        },

        get title() {
            const m = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            return m[this.viewMonth] + ' ' + this.viewYear;
        },
        get displayVal() {
            if (!this.selected) return '';
            const d = this.selected;
            return d.getFullYear() + '-' +
                   String(d.getMonth()+1).padStart(2,'0') + '-' +
                   String(d.getDate()).padStart(2,'0');
        },
        get saveVal() {
            if (!this.selected) return '';
            const d = this.selected;
            return d.getFullYear() + '-' +
                   String(d.getMonth()+1).padStart(2,'0') + '-' +
                   String(d.getDate()).padStart(2,'0') + ' 00:00:00';
        },
        get yearRange() {
            return Array.from({ length: 12 }, (_, i) => this.yearRangeStart + i);
        },
        prevMonth() { this.viewMonth--; if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear--; } },
        nextMonth() { this.viewMonth++; if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++; } },
        prevYearRange() { this.yearRangeStart -= 12; },
        nextYearRange() { this.yearRangeStart += 12; },
        selectDay(d) {
            this.selected = new Date(this.viewYear, this.viewMonth, d);
            $wire.set('hfDate', this.displayVal);
            this.open = false;
        },
        selectMonth(m) { this.viewMonth = m; this.view = 'days'; },
        selectYear(y) { this.viewYear = y; this.view = 'months'; },
        confirm() { $wire.set('hfDate', this.displayVal); this.open = false; this.view = 'days'; },
        daysInMonth() { return new Date(this.viewYear, this.viewMonth + 1, 0).getDate(); },
        firstDay() { return new Date(this.viewYear, this.viewMonth, 1).getDay(); },
        isToday(d) { const n = new Date(); return d === n.getDate() && this.viewMonth === n.getMonth() && this.viewYear === n.getFullYear(); },
        isSelected(d) { return this.selected && d === this.selected.getDate() && this.viewMonth === this.selected.getMonth() && this.viewYear === this.selected.getFullYear(); }
    }"
    x-init="
        initFromWire();
        $watch('hfDateWire', () => initFromWire());
    ">

    <label for="inspectDate" class="block text-sm font-medium text-gray-700 text-center">HF Date</label>

    <div @click="open = !open"
        class="mt-1 flex items-center gap-2 w-full border border-black rounded-md px-2 py-1 cursor-pointer bg-white text-sm"
        :class="selected ? 'text-gray-900' : 'text-gray-400'">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span x-text="displayVal || 'Select a date'" class="flex-1 text-center"></span>
    </div>

    <div x-show="open" @click.outside="open = false; view = 'days'" wire:ignore
        class="absolute z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-72">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-3">
            <button @click="view === 'years' ? prevYearRange() : prevMonth()"
                class="p-1 rounded hover:bg-gray-100 text-gray-500">&lsaquo;</button>

            <div class="flex items-center gap-1">
                <button @click="view = (view === 'months') ? 'days' : 'months'"
                    class="text-sm font-medium px-2 py-0.5 rounded hover:bg-gray-100"
                    :class="view === 'months' ? 'bg-blue-50 text-blue-600' : 'text-gray-700'"
                    x-text="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][viewMonth]">
                </button>
                <button @click="view = (view === 'years') ? 'days' : 'years'"
                    class="text-sm font-medium px-2 py-0.5 rounded hover:bg-gray-100"
                    :class="view === 'years' ? 'bg-blue-50 text-blue-600' : 'text-gray-700'"
                    x-text="viewYear">
                </button>
            </div>

            <button @click="view === 'years' ? nextYearRange() : nextMonth()"
                class="p-1 rounded hover:bg-gray-100 text-gray-500">&rsaquo;</button>
        </div>

        {{-- Days view --}}
        <div x-show="view === 'days'">
            <div class="grid grid-cols-7 mb-1">
                <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                    <div class="text-center text-xs text-gray-400 py-1" x-text="d"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-0.5">
                <template x-for="e in firstDay()">
                    <div></div>
                </template>
                <template x-for="d in daysInMonth()">
                    <button @click="selectDay(d)"
                        class="text-xs py-1.5 rounded text-center"
                        :class="{
                            'bg-blue-600 text-white': isSelected(d),
                            'text-blue-600 font-semibold': isToday(d) && !isSelected(d),
                            'hover:bg-gray-100': !isSelected(d)
                        }"
                        x-text="d">
                    </button>
                </template>
            </div>
        </div>

        {{-- Months view --}}
        <div x-show="view === 'months'" class="grid grid-cols-3 gap-2">
            <template x-for="(m, i) in ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']">
                <button @click="selectMonth(i)"
                    class="text-sm py-2 rounded text-center"
                    :class="viewMonth === i ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700'"
                    x-text="m">
                </button>
            </template>
        </div>

        {{-- Years view --}}
        <div x-show="view === 'years'" class="grid grid-cols-3 gap-2">
            <template x-for="y in yearRange">
                <button @click="selectYear(y)"
                    class="text-sm py-2 rounded text-center"
                    :class="viewYear === y ? 'bg-blue-600 text-white' : 'hover:bg-gray-100 text-gray-700'"
                    x-text="y">
                </button>
            </template>
        </div>

        <button @click="confirm()" x-show="selected && view === 'days'"
            class="mt-3 w-full bg-blue-600 text-white text-sm rounded-lg py-1.5 hover:bg-blue-700">
            Confirm
        </button>
    </div>
</div>
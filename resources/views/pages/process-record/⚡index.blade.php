<?php

use Livewire\Component;

new class extends Component
{
    public function save() {
        $this->dispatch('trigger-save');
    }
};
?>

<div>
     <livewire:notification.pop-up-notif />
     <livewire:notification.session-modal-notification/>
    <livewire:partials::dashboard.checkppf  :currentPage="'defect'" />
    <livewire:partials::dropdown.dropdown />
    <livewire:partials::-process-record.operator-table/>
</div>
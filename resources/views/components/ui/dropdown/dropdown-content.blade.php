<div class="space-y-4 mt-10">
    @foreach($forms as $formId => $form)
        @if(empty($form['parent_form_id']) || ! isset($forms[$form['parent_form_id']]))
            @include('components.ui.dropdown.dropdown-form', [
                'formId' => $formId,
                'form'   => $form,
                'isSub'  => false,
            ])
        @endif
    @endforeach
</div>
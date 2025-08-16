<?php

use App\Livewire\DocumentTrackingForm;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(DocumentTrackingForm::class)
        ->assertStatus(200);
});

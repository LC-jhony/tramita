<?php

use App\Livewire\DocumentForm;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(DocumentForm::class)
        ->assertStatus(200);
});

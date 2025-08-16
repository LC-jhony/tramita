<?php

use App\Livewire\BookClaimForm;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(BookClaimForm::class)
        ->assertStatus(200);
});

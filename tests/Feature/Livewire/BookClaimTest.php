<?php

use App\Livewire\BookClaim;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(BookClaim::class)
        ->assertStatus(200);
});

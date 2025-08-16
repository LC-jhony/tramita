<?php

use App\Livewire\BookClaimForm;
use App\Livewire\DocumentForm;
use App\Livewire\DocumentTrackingForm;
use Illuminate\Support\Facades\Route;

Route::get('/', DocumentForm::class)->name('document.form');
Route::get('/bookclaim', BookClaimForm::class)->name('bookclaim.form');
Route::get('/documenttrackingform', DocumentTrackingForm::class)->name('document-tracking-form');

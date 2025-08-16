<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
</head>

<body class="bg-[#f3f4f6]">
    <x-navbar>
        <x-nav-link :href="route('bookclaim.form')" :active="request()->routeIs('bookclaim.form')">
            {{-- <x-icon type='regular' size='lg' value='user' class='mr-2' /> --}}
            Libro de Reclamos
        </x-nav-link>
        <x-nav-link :href="route('document-tracking-form')" :active="request()->routeIs('document-tracking-form')">
            {{-- <x-icon type='regular' size='lg' value='user' class='mr-2' /> --}}
            Seguimiento
        </x-nav-link>

        <x-nav-link :href="route('filament.admin.auth.login')" :active="request()->routeIs('filament.admin.auth.login')">
            Iniciar Seción
        </x-nav-link>

    </x-navbar>
    {{ $slot }}

    @livewire('notifications') {{-- Only required if you wish to send flash notifications --}}

    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>

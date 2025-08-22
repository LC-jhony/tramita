<x-container>
    <form wire:submit="create">
        <div class="flex justify-end mb-4">
            <x-filament::button type="submit" icon="heroicon-m-folder-plus">
                Registrar Tramite
            </x-filament::button>
        </div>
        {{ $this->form }}

    </form>

    <x-filament-actions::modals />
</x-container>

<x-container>
    <form wire:submit="create">
        <div class="flex justify-end mb-4">
            <x-filament::button icon="heroicon-m-sparkles" >
                New user
            </x-filament::button>
        </div>
        {{ $this->form }}

    </form>

    <x-filament-actions::modals />
</x-container>

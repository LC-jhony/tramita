<?php

namespace App\Livewire;

use Filament\Schemas;
use Livewire\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\ToggleButtons;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class DocumentForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('Registre tramite')
                    ->description(' registre su tramite llene los campos requeridos para registrar su tramite')
                    ->schema([
                        Schemas\Components\Section::make('Datos Personales')
                            ->schema([
                                ToggleButtons::make('representation')
                                    ->label('Representante')
                                    ->boolean(
                                        trueLabel: 'Persona Natura',
                                        falseLabel: 'Persona Juridica'
                                    )
                                    ->icons([
                                        'heroicon-o-briefcase',
                                        'heroicon-o-user',
                                    ])
                                    ->live()
                                    ->grouped()
                                    ->default(true),
                                Schemas\Components\Fieldset::make('Persona Natural')
                                    ->visible(fn($get) => $get('representation') === true)
                                    ->schema([
                                        TextInput::make('dni')
                                            ->label('DNI')
                                            ->numeric()
                                            ->required()
                                            ->requiredWith('representation'),
                                        TextInput::make('full_name')
                                            ->label('Nombre')
                                            ->required()
                                            ->requiredWith('representation'),
                                        TextInput::make('last_name')
                                            ->label('Apellido paterno')
                                            ->required()
                                            ->requiredWith('representation'),
                                        TextInput::make('first_name')
                                            ->label('Apellido materno')
                                            ->required()
                                            ->requiredWith('representation'),
                                    ]),
                                Schemas\Components\Fieldset::make('Persona Juridica')
                                    ->visible(fn($get) => $get('representation') === false)
                                    ->schema([
                                        TextInput::make('ruc')
                                            ->numeric()
                                            ->requiredWith('representation'),
                                        TextInput::make('empresa')
                                            ->requiredWith('representation'),
                                    ]),

                            ])->columnSpan(2),
                        Schemas\Components\Section::make('Datos del Tramite')
                            ->schema([
                                Schemas\Components\Grid::make()
                                    ->schema([
                                        Schemas\Components\Section::make('')
                                            ->schema([
                                                Schemas\Components\Grid::make()
                                                    ->schema([])->columns(2),
                                            ]),
                                        Schemas\Components\Section::make('')
                                            ->schema([
                                                Schemas\Components\Grid::make()
                                                    ->schema([])->columns(2),

                                            ]),
                                    ])->columns(2),
                                Textarea::make('asunto')
                                    ->label('Asunto del documento')
                                    ->required()
                                    ->columnSpan(2),
                            ])->columnSpan(2),
                    ])->columns(4),
                FileUpload::make('attachment')
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        dd($this->form->getState());
    }

    public function render(): View
    {
        return view('livewire.document-form');
    }
}

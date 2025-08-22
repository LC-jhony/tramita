<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                Select::make('origen')
                    ->options(['Internal' => 'Internal', 'External' => 'External'])
                    ->required(),
                Toggle::make('representation')
                    ->required(),
                TextInput::make('full_name')
                    ->default(null),
                TextInput::make('first_name')
                    ->default(null),
                TextInput::make('last_name')
                    ->default(null),
                TextInput::make('dni')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('address')
                    ->default(null),
                TextInput::make('ruc')
                    ->default(null),
                TextInput::make('empresa')
                    ->default(null),
                TextInput::make('document_type_id')
                    ->required()
                    ->numeric(),
                TextInput::make('area_oreigen_id')
                    ->required()
                    ->numeric(),
                TextInput::make('gestion_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('folio')
                    ->required(),
                DatePicker::make('reception_date'),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('condition')
                    ->required(),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'received' => 'Received',
            'in_process' => 'In process',
            'derived' => 'Derived',
            'returned' => 'Returned',
            'archived' => 'Archived',
        ])
                    ->default('draft')
                    ->required(),
            ]);
    }
}

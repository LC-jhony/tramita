<?php

namespace App\Filament\Resources\Documents;

use UnitEnum;
use BackedEnum;
use App\Models\Document;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Filament\Resources\Documents\RelationManagers\DocumentMovementRelationManager;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $modelLabel = "Documento";
    protected static string|BackedEnum|null $navigationIcon = 'sui-document-words';
    protected static string|UnitEnum|null $navigationGroup = 'Gestión Documental';
    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentMovementRelationManager::class,
            RelationManagers\DocumentHistorieRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}

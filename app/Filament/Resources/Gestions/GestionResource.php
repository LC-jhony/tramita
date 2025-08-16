<?php

namespace App\Filament\Resources\Gestions;

use App\Filament\Resources\Gestions\Pages\CreateGestion;
use App\Filament\Resources\Gestions\Pages\EditGestion;
use App\Filament\Resources\Gestions\Pages\ListGestions;
use App\Filament\Resources\Gestions\Pages\ViewGestion;
use App\Filament\Resources\Gestions\Schemas\GestionForm;
use App\Filament\Resources\Gestions\Schemas\GestionInfolist;
use App\Filament\Resources\Gestions\Tables\GestionsTable;
use App\Models\Gestion;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GestionResource extends Resource
{
    protected static ?string $model = Gestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'iconsax-out-task-square';

    protected static string|UnitEnum|null $navigationGroup  = 'Configuracion';
    public static function form(Schema $schema): Schema
    {
        return GestionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GestionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGestions::route('/'),
            'create' => CreateGestion::route('/create'),
            'view' => ViewGestion::route('/{record}'),
            'edit' => EditGestion::route('/{record}/edit'),
        ];
    }
}

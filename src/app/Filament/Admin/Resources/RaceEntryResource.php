<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RaceEntryResource\Pages;
use App\Filament\Admin\Resources\RaceEntryResource\RelationManagers;
use App\Models\RaceEntry;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RaceEntryResource extends Resource
{
    protected static ?string $model = RaceEntry::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListRaceEntries::route('/'),
            'create' => Pages\CreateRaceEntry::route('/create'),
            'edit' => Pages\EditRaceEntry::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\IngestResource\Pages;
use App\Models\Ingest;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;

class IngestResource extends Resource
{
    protected static ?string $model = Ingest::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-newspaper';
    protected static UnitEnum|string|null $navigationGroup = 'Feeds';

    /** Filament v4 signature */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('url')->url()->required(),
            TextInput::make('image_url')->url(),
            TextInput::make('license_tag'),
            Select::make('status')->options([
                'draft' => 'Draft',
                'published' => 'Published',
                'hidden' => 'Hidden',
            ])->default('draft'),
            DateTimePicker::make('published_at'),
            Textarea::make('summary')->rows(4),
        ]);
    }

    /** Filament v4 Table remains Table $table : Table */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(60),
                TextColumn::make('source.name')->label('Source')->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                BadgeColumn::make('status')->colors([
                    'warning' => 'draft',
                    'success' => 'published',
                    'gray' => 'hidden',
                ]),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIngests::route('/'),
            'edit' => Pages\EditIngest::route('/{record}/edit'),
        ];
    }
}

namespace App\Filament\Admin\Resources\IngestResource\Pages;

use App\Filament\Admin\Resources\IngestResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

class ListIngests extends ListRecords
{
    protected static string $resource = IngestResource::class;
}

class EditIngest extends EditRecord
{
    protected static string $resource = IngestResource::class;
}

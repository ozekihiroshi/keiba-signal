<?php

namespace App\Filament\Admin\Resources\RaceEntryResource\Pages;

use App\Filament\Admin\Resources\RaceEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRaceEntry extends EditRecord
{
    protected static string $resource = RaceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

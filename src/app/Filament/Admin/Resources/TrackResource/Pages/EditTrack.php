<?php

namespace App\Filament\Admin\Resources\TrackResource\Pages;

use App\Filament\Admin\Resources\TrackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrack extends EditRecord
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\TrackResource\Pages;

use App\Filament\Admin\Resources\TrackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTracks extends ListRecords
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

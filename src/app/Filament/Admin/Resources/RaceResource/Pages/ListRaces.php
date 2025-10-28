<?php

namespace App\Filament\Admin\Resources\RaceResource\Pages;

use App\Filament\Admin\Resources\RaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRaces extends ListRecords
{
    protected static string $resource = RaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

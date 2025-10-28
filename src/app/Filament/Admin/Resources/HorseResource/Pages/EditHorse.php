<?php

namespace App\Filament\Admin\Resources\HorseResource\Pages;

use App\Filament\Admin\Resources\HorseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHorse extends EditRecord
{
    protected static string $resource = HorseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

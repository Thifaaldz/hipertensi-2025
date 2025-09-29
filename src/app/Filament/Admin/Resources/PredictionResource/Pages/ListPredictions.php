<?php

namespace App\Filament\Admin\Resources\PredictionResource\Pages;

use App\Filament\Admin\Resources\PredictionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPredictions extends ListRecords
{
    protected static string $resource = PredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

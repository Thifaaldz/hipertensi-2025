<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Prediction;

class PredictionMap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static string $view = 'filament.admin.pages.prediction-map';
    protected static ?string $navigationLabel = 'Peta Prediksi';
    protected static ?string $title = 'Peta Prediksi Hipertensi';

    public array $predictions = [];

    public function mount(): void
    {
        $this->predictions = Prediction::whereNotNull('lat')
            ->whereNotNull('lon')
            ->get([
                'kecamatan','wilayah','tahun','persentase','prioritas',
                'predicted_route','focus_date','lat','lon'
            ])
            ->toArray();
    }
}

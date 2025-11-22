<?php

namespace App\Filament\Admin\Resources\PredictionResource\Pages;

use App\Filament\Admin\Resources\PredictionResource;
use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListPredictions extends ListRecords
{
    protected static string $resource = PredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Jalankan ML dari dataset yang ada
            |--------------------------------------------------------------------------
            */
            Action::make('run_ml')
                ->label('Jalankan ML')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->action(function () {

                    Artisan::call('ml:predict');

                    $output = Artisan::output();

                    Notification::make()
                        ->title('ML selesai dijalankan')
                        ->body(nl2br($output))
                        ->success()
                        ->send();
                }),

            /*
            |--------------------------------------------------------------------------
            | Upload dataset baru + langsung jalankan ML
            |--------------------------------------------------------------------------
            */
            Action::make('upload_new_dataset')
                ->label('Upload Dataset Baru & Jalankan ML')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('dataset')
                        ->label('Upload Dataset (CSV)')
                        ->acceptedFileTypes(['text/csv'])
                        ->directory('ml_input')
                        ->preserveFilenames()
                        ->required(),
                ])
                ->action(function (array $data) {

                    if (! isset($data['dataset'])) {
                        throw new Exception('Harap upload file terlebih dahulu.');
                    }

                    $uploaded = storage_path('app/' . $data['dataset']);

                    if (! file_exists($uploaded)) {
                        throw new Exception("File upload tidak ditemukan: {$uploaded}");
                    }

                    // replace dataset utama
                    copy($uploaded, storage_path('app/ml_input/dataset.csv'));

                    // jalankan ML
                    Artisan::call('ml:predict');

                    $output = Artisan::output();

                    Notification::make()
                        ->title('Dataset Baru Dipasang & ML Dijalankan')
                        ->body(nl2br($output))
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}

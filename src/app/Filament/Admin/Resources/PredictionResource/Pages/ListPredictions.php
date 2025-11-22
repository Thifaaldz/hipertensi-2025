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
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListPredictions extends ListRecords
{
    protected static string $resource = PredictionResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('run_ml')
                ->label('Jalankan ML')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->action(function () {

                    Artisan::call('ml:predict');

                    Notification::make()
                        ->title('ML selesai dijalankan')
                        ->body(nl2br(Artisan::output()))
                        ->success()
                        ->send();
                }),

            Action::make('upload_new_dataset')
                ->label('Upload Dataset Baru & Jalankan ML')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('dataset')
                        ->label('Upload Dataset (XLSX/CSV)')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ])
                        ->directory('ml_input/uploads')
                        ->disk('private') // ✅ penting
                        ->preserveFilenames()
                        ->required(),
                ])
                ->action(function (array $data) {

                    if (! isset($data['dataset'])) {
                        throw new Exception('Harap upload file terlebih dahulu.');
                    }

                    // ✅ Ambil path file hasil upload
                    $uploadedPath = Storage::disk('private')->path($data['dataset']);

                    if (! file_exists($uploadedPath)) {
                        throw new Exception("File upload tidak ditemukan: {$uploadedPath}");
                    }

                    // ✅ lokasi dataset utama CSV
                    $mainCsv = storage_path('app/ml_input/dataset.csv');

                    // ✅ buat folder arsip jika belum ada
                    $archiveDir = storage_path('app/ml_input/archive');
                    if (! is_dir($archiveDir)) {
                        mkdir($archiveDir, 0755, true);
                    }

                    // ✅ arsipkan dataset lama jika ada
                    if (file_exists($mainCsv)) {
                        $timestamp = now()->format('Ymd_His');
                        rename($mainCsv, $archiveDir . "/dataset_{$timestamp}.csv");
                    }

                    // ✅ jika upload masih XLSX → convert ke CSV
                    if (str_ends_with($uploadedPath, '.xlsx')) {

                        $spreadsheet = IOFactory::load($uploadedPath);
                        $csvWriter = IOFactory::createWriter($spreadsheet, 'Csv');
                        $csvWriter->save($mainCsv);

                    } else {
                        // ✅ jika CSV langsung replace
                        copy($uploadedPath, $mainCsv);
                    }

                    // ✅ jalankan ML setelah replace dataset
                    Artisan::call('ml:predict');

                    Notification::make()
                        ->title('Dataset Baru Dipasang & ML Dijalankan')
                        ->body(nl2br(Artisan::output()))
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}

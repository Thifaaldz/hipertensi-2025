<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ConvertExcel extends Command
{
    protected $signature = 'ml:convert-excel {file=storage/app/ml_input/dataset.xlsx}';
    protected $description = 'Convert Excel dataset.xlsx menjadi dataset.csv untuk ML';

    public function handle()
    {
        $file = base_path($this->argument('file'));
        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: $file");
            return 1;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $csvPath = storage_path('app/ml_input/dataset.csv');
        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save($csvPath);

        $this->info("✅ Berhasil convert ke $csvPath");
        return 0;
    }
}

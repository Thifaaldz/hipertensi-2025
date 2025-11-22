<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use App\Models\Prediction;
use League\Csv\Reader;

class RunMlPredict extends Command
{
    protected $signature = 'ml:predict {--input=} {--geo=}';
    protected $description = 'Run python ML and import predictions into DB';

    public function handle()
    {
        $this->info("Starting ML prediction...");

        // Default file paths
        $input  = $this->option('input') ?? 'ml_input/dataset.csv';
        $geo    = $this->option('geo')   ?? 'ml_input/penelitian.geojson';
        $output = storage_path('app/ml_output/predictions.csv');

        $input_full = storage_path('app/' . $input);
        $geo_full   = storage_path('app/' . $geo);
        $py         = base_path('ml/main_ml.py');

        // ✅ Validate input dataset
        if (!file_exists($input_full)) {
            $this->error("Input CSV not found: $input_full");
            return Command::FAILURE;
        }

        if (!file_exists($py)) {
            $this->error("Python ML script not found: $py");
            return Command::FAILURE;
        }

        // ✅ Use venv if exists
        $venv = base_path('venv/bin/python');
        $python = file_exists($venv) ? $venv : 'python3';

        $this->info("Running python using: " . $python);

        // ✅ Execute Python
        $process = new Process([
            $python,
            $py,
            '--input_csv', $input_full,
            '--geojson', $geo_full,
            '--output_csv', $output,
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error("ML process failed.");
            return Command::FAILURE;
        }

        if (!file_exists($output)) {
            $this->error("Output CSV not found: $output");
            return Command::FAILURE;
        }

        // ✅ Detect correct year from dataset
        $csv_year = $this->extractYearFromCSV($input_full);

        if (!$csv_year) {
            $this->error("Dataset tidak memiliki kolom tahun atau periode_data yang valid.");
            return Command::FAILURE;
        }

        $this->info("Detected dataset year: $csv_year");

        // ✅ Archive old records
        Prediction::where('tahun', '!=', $csv_year)->update(['is_archived' => true]);

        // ✅ Import prediction results
        $this->info("Importing predictions...");
        $csv = Reader::createFromPath($output, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $row) {
            Prediction::updateOrCreate(
                [
                    'kecamatan' => $row['kecamatan_final'] ?? $row['kecamatan'],
                    'tahun' => (int) (
                        $row['tahun']
                        ?? $row['periode_data']
                        ?? $csv_year
                    ),
                ],
                [
                    'wilayah'         => $row['wilayah'] ?? null,
                    'persentase'      => isset($row['persentase']) ? (float) $row['persentase'] : null,
                    'prioritas'       => $row['prioritas'] ?? null,
                    'lat'             => isset($row['lat']) ? (float) $row['lat'] : null,
                    'lon'             => isset($row['lon']) ? (float) $row['lon'] : null,
                    'predicted_route' => $row['predicted_route'] ?? null,
                    'focus_month'     => $row['focus_month'] ?? null,
                    'focus_date'      => $row['focus_date'] ?? null,
                    'meta'            => json_encode($row),
                    'is_archived'     => false,
                ]
            );
        }

        $this->info("ML prediction import completed.");
        return Command::SUCCESS;
    }

    private function extractYearFromCSV($file)
    {
        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $row) {

            // ✅ Case 1 — column "tahun" exists
            if (isset($row['tahun']) && is_numeric($row['tahun'])) {
                return (int) $row['tahun'];
            }

            // ✅ Case 2 — dataset uses "periode_data"
            if (isset($row['periode_data']) && is_numeric($row['periode_data'])) {
                return (int) $row['periode_data'];
            }
        }

        return null;
    }
}

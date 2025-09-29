<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use App\Models\Prediction;
use League\Csv\Reader;

class RunMlPredict extends Command
{
    protected $signature = 'ml:predict {--input= : storage path to dataset CSV} {--geo= : storage path to geojson}';
    protected $description = 'Run python ML and import predictions into DB';

    public function handle()
    {
        $this->info("🚀 Starting ML prediction...");

        $input  = $this->option('input') ?? 'ml_input/dataset.csv';
        $geo    = $this->option('geo')   ?? 'ml_input/penelitian.geojson';
        $output = storage_path('app/ml_output/predictions.csv');

        // Ensure paths
        $input_full = storage_path('app/' . $input);
        $geo_full   = storage_path('app/' . $geo);

        // Path to python script
        $py = base_path('ml/main_ml.py');

        if (!file_exists($input_full)) {
            $this->error("❌ Input CSV not found at: $input_full");
            return 1;
        }

        if (!file_exists($py)) {
            $this->error("❌ Python ML script not found at: $py");
            return 1;
        }

        // Use venv python if available
        $venvPython = base_path('venv/bin/python');
        $pythonExec = file_exists($venvPython) ? $venvPython : 'python3';

        $this->info("Using Python: " . $pythonExec);

        $process = new Process([
            $pythonExec,
            $py,
            '--input_csv', $input_full,
            '--geojson', $geo_full,
            '--output_csv', $output,
        ]);

        $process->setTimeout(3600); // up to 1 hour
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error("❌ ML process failed.");
            return 1;
        }

        if (!file_exists($output)) {
            $this->error("❌ Output file not found: $output");
            return 1;
        }

        $this->info("📥 Importing predictions to DB...");
        $csv = Reader::createFromPath($output, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        foreach ($records as $row) {
            Prediction::updateOrCreate(
                [
                    'kecamatan' => $row['kecamatan_final'] ?? $row['kecamatan'],
                    'tahun'     => isset($row['tahun']) ? (int)$row['tahun'] : null,
                ],
                [
                    'wilayah'         => $row['wilayah'] ?? null,
                    'persentase'      => isset($row['persentase']) ? (float)$row['persentase'] : null,
                    'prioritas'       => $row['prioritas'] ?? null,
                    'lat'             => isset($row['lat']) ? (float)$row['lat'] : null,
                    'lon'             => isset($row['lon']) ? (float)$row['lon'] : null,
                    'predicted_route' => $row['predicted_route'] ?? null,
                    'focus_month'     => $row['focus_month'] ?? null,
                    'focus_date'      => $row['focus_date'] ?? null,
                    'meta'            => json_encode($row),
                ]
            );
        }

        $this->info("✅ Import completed.");
        return 0;
    }
}

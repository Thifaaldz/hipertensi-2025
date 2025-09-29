<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Prediction;

class MlController extends Controller
{
    // return raw geojson file (content-type application/json)
    public function geojson()
    {
        $path = storage_path('app/ml_input/penelitian.geojson');
        if (!file_exists($path)) {
            return response()->json(['error' => 'GeoJSON not found'], 404);
        }
        $contents = file_get_contents($path);
        return response($contents, 200)->header('Content-Type', 'application/json');
    }

    // return predictions as GeoJSON FeatureCollection-like array (or plain JSON)
    public function predictionsJson(Request $req)
    {
        $query = Prediction::query();

        if ($req->filled('tahun')) {
            $query->where('tahun', $req->tahun);
        }

        // optional: filter by prioritas
        if ($req->filled('prioritas')) {
            $query->where('prioritas', $req->prioritas);
        }

        $rows = $query->get(['kecamatan','wilayah','tahun','persentase','prioritas','predicted_route','focus_date','lat','lon']);

        // Convert to GeoJSON-ish features
        $features = [];
        foreach ($rows as $r) {
            if ($r->lat === null || $r->lon === null) continue;
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'kecamatan' => $r->kecamatan,
                    'wilayah' => $r->wilayah,
                    'tahun' => $r->tahun,
                    'persentase' => $r->persentase,
                    'prioritas' => $r->prioritas,
                    'predicted_route' => $r->predicted_route,
                    'focus_date' => optional($r->focus_date)->toDateString(),
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$r->lon, (float)$r->lat],
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}

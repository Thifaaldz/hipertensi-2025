<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Prediction;

class HomePage extends Component
{
    public $predictions = [];

    public function mount()
    {
        $this->predictions = Prediction::whereNotNull('lat')
            ->whereNotNull('lon')
            ->get([
                'kecamatan', 'wilayah', 'tahun', 'persentase', 'prioritas',
                'predicted_route', 'focus_date', 'lat', 'lon'
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.home-page', [
            'routes' => collect($this->predictions)->pluck('predicted_route')->unique(),
            'years'  => collect($this->predictions)->pluck('tahun')->unique(),
        ]);
    }
}

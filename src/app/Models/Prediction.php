<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
        protected $fillable = [
        'kecamatan','wilayah','tahun','persentase','prioritas','lat','lon',
        'predicted_route','focus_month','focus_date','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'focus_date' => 'date',
    ];
}

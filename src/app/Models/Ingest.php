<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingest extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'guid',
        'hash',
        'url',
        'title',
        'summary_raw',
        'summary',
        'image_url',
        'published_at',
        'lang',
        'license_tag',
        'raw_json',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'raw_json' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(\App\Models\Source::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'base_url',
        'rss_url',
        'license_tag',
        'robots_allowed',
        'fetch_interval_minutes',
        'last_fetched_at',
    ];

    protected $casts = [
        'robots_allowed' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    public function ingests()
    {
        return $this->hasMany(\App\Models\Ingest::class);
    }
}

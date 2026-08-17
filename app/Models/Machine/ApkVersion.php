<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApkVersion extends Model
{
    protected $fillable = [
        'version_name',
        'version_code',
        'flavor',
        'file_path',
        'download_url',
        'release_notes',
        'is_forced',
    ];

    protected $casts = [
        'is_forced' => 'boolean',
    ];

    /**
     * 取得最終下載 URL
     */
    public function getUrlAttribute(): string
    {
        return $this->download_url ?: Storage::disk('public')->url($this->file_path);
    }
}

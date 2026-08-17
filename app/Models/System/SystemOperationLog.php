<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use App\Models\System\User;
use App\Models\System\Company;

class SystemOperationLog extends Model
{
    use HasFactory, TenantScoped;
    
    protected $appends = [
        'translated_note',
    ];

    /**
     * 動態翻譯備註
     */
    public function getTranslatedNoteAttribute(): ?string
    {
        if (empty($this->note)) {
            return null;
        }

        // 過濾掉非字串/數值的數值，避免 Laravel 翻譯引擎處理巢狀陣列時報錯
        $replacements = collect(array_merge(
            $this->old_values ?? [],
            $this->new_values ?? []
        ))->filter(fn($val) => is_scalar($val))->toArray();

        return __($this->note, $replacements);
    }


    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'action',
        'target_id',
        'target_type',
        'old_values',
        'new_values',
        'note',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Polymorphic relationship to the target entity
     */
    public function target()
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * Get the name of the target entity from relation or values
     */
    public function getTargetNameAttribute(): string
    {
        // 1. Try from loaded relationship
        if ($this->target) {
            if (method_exists($this->target, 'getLocalizedNameAttribute')) {
                return $this->target->localized_name;
            }
            return $this->target->name ?? '';
        }

        // 2. Try from values
        $name = $this->new_values['name'] ?? $this->old_values['name'] ?? 
                $this->new_values['code'] ?? $this->old_values['code'] ?? null;
        if ($name) {
            return $name;
        }

        return '';
    }
}

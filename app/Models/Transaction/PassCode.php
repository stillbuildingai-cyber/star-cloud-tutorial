<?php

namespace App\Models\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassCode extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'name',
        'code',
        'slug',
        'batch_no',
        'expires_at',
        'status',
        'usage_limit',   // 可用次數上限；null=無限次。勾「只能使用一次」時=1
        'usage_count',   // 已使用次數
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * 獲取公開通行連結
     */
    public function getTicketUrlAttribute()
    {
        return $this->slug ? route('pass-code.ticket', $this->slug) : null;
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($passCode) {
            if (!$passCode->slug) {
                $passCode->slug = \Illuminate\Support\Str::random(16);
            }
        });
    }

    /**
     * 關聯機台
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * 關聯建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 判斷是否可用
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // 有設使用次數上限(如「只能使用一次」usage_limit=1)且已用完 → 失效。null=無限次。
        if (!is_null($this->usage_limit) && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * 產生唯一的通行碼 (8位)
     */
    public static function generateUniqueCode(int $machineId): string
    {
        do {
            $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $exists = self::where('machine_id', $machineId)
                ->where('code', $code)
                ->where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->exists();
        } while ($exists);

        return $code;
    }
}

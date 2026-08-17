<?php

namespace App\Models\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelcomeGift extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'name',
        'code',
        'slug',
        'discount_type',
        'discount_value',
        'usage_type',
        'usage_limit',
        'usage_count',
        'expires_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'discount_value' => 'integer',
    ];

    protected $appends = ['discount_label', 'input_fold'];

    /**
     * 獲取公開來店禮連結
     */
    public function getTicketUrlAttribute()
    {
        return $this->slug ? route('welcome-gift.ticket', $this->slug) : null;
    }

    protected static function booted()
    {
        static::creating(function ($gift) {
            if (!$gift->slug) {
                $gift->slug = \Illuminate\Support\Str::random(16);
            }
        });
    }

    /**
     * 後台編輯時回填使用的折數 (如趴數 20 換算回 8 折，15 換算回 8.5 折)
     */
    public function getInputFoldAttribute()
    {
        if ($this->discount_type !== 'percentage') {
            return null;
        }
        return (100 - $this->discount_value) / 10;
    }

    /**
     * 自動轉換為台灣消費者習慣的折數標籤 (例如：八折、八五折、折 50 元)
     */
    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === 'amount') {
            return "折 {$this->discount_value} 元";
        }

        if ($this->discount_type === 'percentage') {
            $fold = $this->input_fold;
            return $this->convertToChineseDiscount($fold);
        }

        return '';
    }

    private function convertToChineseDiscount($fold): string
    {
        $map = [
            '1' => '一', '2' => '二', '3' => '三', '4' => '四', '5' => '五',
            '6' => '六', '7' => '七', '8' => '八', '9' => '九', '0' => '零'
        ];
        
        $foldStr = (string)$fold;
        if (strpos($foldStr, '.') !== false) {
            list($integer, $decimal) = explode('.', $foldStr);
            $intChar = $map[$integer] ?? $integer;
            $decChar = $map[substr($decimal, 0, 1)] ?? substr($decimal, 0, 1);
            return "{$intChar}{$decChar}折";
        }
        
        $char = $map[$foldStr] ?? $foldStr;
        return "{$char}折";
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

        if ($this->usage_type === 'once' && $this->usage_count >= ($this->usage_limit ?? 1)) {
            return false;
        }

        return true;
    }

    /**
     * 產生唯一的來店禮代碼 (8位)
     */
    public static function generateUniqueCode(int $machineId): string
    {
        do {
            $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $exists = self::where('machine_id', $machineId)
                ->where('code', $code)
                ->where('status', 'active')
                ->exists();
        } while ($exists);

        return $code;
    }
}

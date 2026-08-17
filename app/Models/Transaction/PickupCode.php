<?php

namespace App\Models\Transaction;

use App\Models\Machine\Machine;
use App\Models\System\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupCode extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'product_id',
        'slot_no',
        'code',
        'slug',
        'batch_no',
        'status',
        'expires_at',
        'used_at',
        'usage_limit',
        'usage_count',
        'created_by',
        'order_id',
    ];

    /**
     * 獲取公開取貨連結
     */
    public function getTicketUrlAttribute()
    {
        return $this->slug ? route('pickup.ticket', $this->slug) : route('pickup.ticket', $this->code);
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($pickupCode) {
            $pickupCode->slug = \Illuminate\Support\Str::random(16);
        });
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * 關聯機台
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * 關聯商品（取貨碼改綁商品時使用）
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }

    /**
     * 關聯建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 關聯訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 判斷是否可用
     */
    public function isValid(): bool
    {
        return $this->status === 'active'
            && ($this->usage_count < $this->usage_limit)
            && ($this->expires_at?->isFuture() ?? true); // null expires_at = 無到期限制
    }

    /**
     * 產生唯一的取貨碼 (8位)
     */
    public static function generateUniqueCode(int $machineId): string
    {
        do {
            $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $exists = self::where('machine_id', $machineId)
                ->where('code', $code)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->exists();
        } while ($exists);

        return $code;
    }
}

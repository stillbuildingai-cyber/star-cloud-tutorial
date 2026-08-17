<?php

namespace App\Models\Machine;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Product\Product;
use App\Models\System\User;

class MachineStockMovement extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'machine_id',
        'product_id',
        'slot_no',
        'type',
        'quantity',
        'before_qty',
        'after_qty',
        'reference_type',
        'reference_id',
        'note',
        'context',
        'created_by',
    ];
    protected $appends = [
        'translated_note',
        'translated_type',
    ];

    public function getTranslatedNoteAttribute(): ?string
    {
        if (empty($this->note)) {
            return null;
        }

        return __($this->note, $this->context ?? []);
    }

    /**
     * 動態翻譯類型
     */
    public function getTranslatedTypeAttribute(): string
    {
        return __("movement.type.{$this->type}");
    }

    protected $casts = [
        'quantity'   => 'integer',
        'before_qty' => 'integer',
        'after_qty'  => 'integer',
        'context'    => 'array',
    ];

    // ─── 異動類型常數 ───

    const TYPE_REPLENISHMENT   = 'replenishment';   // 補貨單完成
    const TYPE_PICKUP          = 'pickup';           // 取貨碼核銷
    const TYPE_REMOTE_DISPENSE = 'remote_dispense';  // 遠端出貨 (B055 樂觀扣除)
    const TYPE_SALE            = 'sale';             // 一般訂單銷售
    const TYPE_ADJUSTMENT      = 'adjustment';       // B009 回報 / 後台手動修改
    const TYPE_ROLLBACK        = 'rollback';         // B055 出貨失敗回退
    const TYPE_DECOMMISSION    = 'decommission';     // B009 全量同步時移除不在清單的貨道

    // ─── 關聯 ───

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 多型關聯：補貨單、取貨碼操作記錄、遠端指令等
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── 輔助方法 ───

    /**
     * 是否為增加庫存的類型
     */
    public function isInbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_REPLENISHMENT,
            self::TYPE_ROLLBACK,
        ]);
    }

    /**
     * 是否為減少庫存的類型
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_PICKUP,
            self::TYPE_REMOTE_DISPENSE,
            self::TYPE_SALE,
        ]);
    }
}

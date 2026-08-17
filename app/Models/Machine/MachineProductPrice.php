<?php

namespace App\Models\Machine;

use App\Jobs\Product\SendProductSyncCommandJob;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 機台專屬定價：特定機台 × 特定商品的價格覆蓋。
 *
 * - price / member_price 為 NULL 代表沿用全域 products 價。
 * - 任何新增/修改/刪除都會推送 B012 同步指令給「該台」機台，使其即時重抓目錄。
 *   目錄覆蓋於 B012 請求當下即時套用（見 ProductCatalogService::getMachinePayload），
 *   不進公司目錄快取，故改價不需失效公司快取。
 */
class MachineProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'product_id',
        'price',
        'member_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'member_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $row) => $row->dispatchSync());
        static::deleted(fn (self $row) => $row->dispatchSync());
    }

    /**
     * 推送 B012 同步指令給該機台，讓它即時重抓最新定價。
     * 控制台/離線判定沿用 SendProductSyncCommandJob 既有邏輯。
     */
    protected function dispatchSync(): void
    {
        SendProductSyncCommandJob::dispatch($this->machine_id, __('Machine pricing updated'));
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

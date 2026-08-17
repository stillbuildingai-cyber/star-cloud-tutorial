<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use App\Traits\HasDisplayFlowId;
use App\Models\Machine\Machine;
use App\Models\Member\Member;

class Order extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, HasDisplayFlowId;

    // 訂單類型 (order_type)
    public const TYPE_SALE = 'sale';                    // 一般銷售
    public const TYPE_PHARMACY_PICKUP = 'pharmacy_pickup'; // 領藥單
    public const TYPE_MANUAL = 'manual';                // 手動補單（後台人工補登，機台斷線漏報時使用）

    // 支付狀態
    public const PAYMENT_STATUS_FAILED = 0;
    public const PAYMENT_STATUS_SUCCESS = 1;

    // 出貨狀態
    public const DELIVERY_STATUS_FAILED = 0;
    public const DELIVERY_STATUS_SUCCESS = 1;
    public const DELIVERY_STATUS_PARTIAL = 2;

    // 交易生命週期狀態（status 欄位，字串）。
    // pending：進入付款、等待結果；completed：付款成功（出貨另看 delivery_status）；
    // failed：付款失敗/逾時；abandoned：感應前取消（由排程把逾時 pending 標記）。
    // ⚠ 終態 = [completed, failed, abandoned]，一律冪等不可覆蓋（保護線上 main 機台行為不變）。
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ABANDONED = 'abandoned';
    // 領藥單專用：已開立、等待病人到機台領取（非 pending，不被逾時清掃；非終態，可被出貨 finalize 更新）。
    public const STATUS_AWAITING_PICKUP = 'awaiting_pickup';

    /** 終態清單：到了這些狀態就不再變更（冪等）。 */
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_ABANDONED,
    ];

    protected $fillable = [
        'company_id',
        'flow_id',
        'order_no',
        'order_type',
        'pricing_slip_no',
        'created_by',
        'machine_id',
        'member_id',
        'payment_type',
        'total_amount',
        'discount_amount',
        'pay_amount',
        'change_amount',
        'cash_detail',
        'points_used',
        'original_amount',
        'payment_status',
        'payment_request',
        'payment_response',
        'payment_at',
        'member_barcode',
        'code_id',
        'welcome_gift_id',
        'invoice_info',
        'machine_time',
        'status',
        'metadata',
        'delivery_status',
        'remark',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'cash_detail' => 'array',
        'original_amount' => 'decimal:2',
        'payment_at' => 'datetime',
        'machine_time' => 'datetime',
        'metadata' => 'array',
        'payment_type' => 'integer',
        'delivery_status' => 'integer',
    ];

    /**
     * 現金收款摘要字串：「收：{總收金額}（只列有收到的面額明細）　找：{找零}」。
     * 例：收：120（佰:1、10:2）　找：30。無 cash_detail（非現金或舊資料）或全為 0 時回 null。
     * 總收金額由各面額張數加總得出，與括號內明細一致。
     */
    public function getCashReceivedSummaryAttribute(): ?string
    {
        $cd = $this->cash_detail;
        if (empty($cd)) {
            return null;
        }
        // 面額顯示標籤 + 幣值（依大到小）
        $denoms = [
            'b1000' => ['label' => '仟', 'unit' => 1000],
            'b500'  => ['label' => '伍佰', 'unit' => 500],
            'b100'  => ['label' => '佰', 'unit' => 100],
            'c50'   => ['label' => '50', 'unit' => 50],
            'c10'   => ['label' => '10', 'unit' => 10],
            'c5'    => ['label' => '5', 'unit' => 5],
            'c1'    => ['label' => '1', 'unit' => 1],
        ];
        $received = 0;
        $parts = [];
        foreach ($denoms as $key => $d) {
            $n = (int) ($cd[$key] ?? 0);
            if ($n > 0) {
                $parts[] = $d['label'] . ':' . $n;   // 只列有收到的幣別
                $received += $n * $d['unit'];
            }
        }
        if (empty($parts)) {
            return null;
        }
        return '收：' . $received . '（' . implode('、', $parts) . '）　找：' . number_format($this->change_amount, 0);
    }

    /**
     * 取得所有支付類型的對照標籤
     */
    public static function getPaymentTypeLabels(): array
    {
        return [
            1 => __('Credit Card'),
            2 => __('E-Ticket (EasyCard/iPass)'),
            3 => __('QR Code Payment - E.SUN'), // 掃碼支付-玉山
            4 => __('Bill Acceptor'),
            5 => __('Pass Code'), // 使用者要求由「通關密碼」改為「通行碼」
            6 => __('Pickup Code'),
            7 => __('Welcome Gift'),
            8 => __('Questionnaire'),
            9 => __('Coin Acceptor'),
            10 => __('Mobile Pay'), // NFC 手機感應（行動支付，信用卡軌道，依使用者按鈕分類）
            11 => __('QR Code Payment - TayPay'), // 掃碼支付-TayPay
            21 => __('Offline + 1'),
            22 => __('Offline + 2'),
            23 => __('Offline + 3'),
            24 => __('Offline + 4'),
            25 => __('Offline + 9'),
            30 => __('LINE Pay'),
            31 => __('JKO Pay'),
            32 => __('Easy Wallet'),
            33 => __('Pi Pay'),
            34 => __('PlusPay'),
            40 => __('Member Verify Pickup'),
            41 => __('Staff Card'),
            42 => __('Pickup Voucher'),
            50 => __('Offline + LINE Pay'),
            51 => __('Offline + JKO Pay'),
            52 => __('Offline + Easy Wallet'),
            53 => __('Offline + Pi Pay'),
            54 => __('Offline + PlusPay'),
            60 => __('Points/Voucher'),
            61 => __('Member + 1'),
            62 => __('Member + 2'),
            63 => __('Member + 3'),
            64 => __('Member + 4'),
            65 => __('Member + 5'),
            66 => __('Member + 6'),
            67 => __('Member + 7'),
            68 => __('Member + 8'),
            69 => __('Member + 9'),
            70 => __('LINE Pay (Official)'), // LINE Pay 官方直連（非 TapPay 底下的 30）
            90 => __('Member + LINE Pay'),
            91 => __('Member + JKO Pay'),
            92 => __('Member + Easy Wallet'),
            93 => __('Member + Pi Pay'),
            94 => __('Member + PlusPay'),
        ];
    }

    /**
     * 取得目前的支付類型標籤 (Accessor)
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return self::getPaymentTypeLabels()[$this->payment_type] ?? __('Unknown');
    }

    public function getMaskedPickupRecipientAttribute(): ?string
    {
        if (empty($this->member_barcode)) {
            return null;
        }

        return self::maskPickupRecipientName($this->member_barcode);
    }

    public static function maskPickupRecipientName(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/^([^\s（(]+)(.*)$/us', $value, $matches) !== 1) {
            return self::maskName($value);
        }

        return self::maskName($matches[1]) . ($matches[2] ?? '');
    }

    private static function maskName(string $name): string
    {
        $length = mb_strlen($name);

        if ($length <= 1) {
            return $name;
        }

        if ($length === 2) {
            return mb_substr($name, 0, 1) . 'O';
        }

        return mb_substr($name, 0, 1)
            . str_repeat('O', $length - 2)
            . mb_substr($name, -1);
    }

    /** 一般銷售訂單 */
    public function scopeSales($query)
    {
        return $query->where('order_type', self::TYPE_SALE);
    }

    /** 領藥單 */
    public function scopePharmacyPickup($query)
    {
        return $query->where('order_type', self::TYPE_PHARMACY_PICKUP);
    }

    /** 是否為後台手動補單 */
    public function getIsManualAttribute(): bool
    {
        return $this->order_type === self::TYPE_MANUAL;
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    /** 建單者 (領藥單藥師) */
    public function creator()
    {
        return $this->belongsTo(\App\Models\System\User::class, 'created_by');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function dispenseRecords()
    {
        return $this->hasMany(DispenseRecord::class);
    }

    /** 領藥單對應的領藥碼（一單一碼） */
    public function pickupCode()
    {
        return $this->hasOne(PickupCode::class);
    }

    public function staffCardLog()
    {
        return $this->hasOne(\App\Models\StaffCardLog::class);
    }

    public function welcomeGift()
    {
        return $this->belongsTo(WelcomeGift::class);
    }

    /**
     * 取得所有出貨狀態的對照標籤
     */
    public static function getDeliveryStatusLabels(): array
    {
        return [
            self::DELIVERY_STATUS_FAILED => __('Dispense Failed'),
            self::DELIVERY_STATUS_SUCCESS => __('Dispense Success'),
            self::DELIVERY_STATUS_PARTIAL => __('Partial Dispense Success'),
        ];
    }

    /**
     * 出貨狀態是否有意義：唯有付款成功(completed/paid)、機台真的動作過的單才有出貨結果。
     * pending(未完成)、failed(支付失敗)、abandoned(未支付)從未出貨，出貨狀態應顯示 '--'。
     * ⚠ delivery_status 欄位 DB 預設為 1(出貨成功)，這些單若直接讀值會誤顯示成「出貨成功」。
     */
    public function hasDeliveryOutcome(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, 'paid'], true);
    }

    /**
     * 取得目前出貨狀態標籤 (Accessor)
     */
    public function getDeliveryStatusLabelAttribute(): string
    {
        if (! $this->hasDeliveryOutcome()) {
            return '--';
        }

        return self::getDeliveryStatusLabels()[$this->delivery_status] ?? __('Unknown');
    }
}

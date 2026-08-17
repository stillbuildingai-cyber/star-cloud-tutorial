<?php

namespace App\Services\Invoice;

use App\Models\Machine\Machine;
use App\Models\Transaction\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 綠界電子發票 B2C API 服務（後台對帳/補開/作廢用）。
 *
 * 機台端在出貨時已「直連綠界」開立並把結果隨 finalize 上報；本服務負責後台側：
 *  - GetIssue：對 pending 發票以 RelateNumber 去綠界查詢真實狀態（處理掉包/未知，避免重複開立）
 *  - Issue   ：對確認未開立者補開（沿用同一 RelateNumber，冪等）
 *  - Invalid ：對「已開票未出貨」等情況作廢
 *
 * 金鑰來源：機台關聯的金流配置 payment_configs.settings.ecpay_invoice（store_id/hash_key/hash_iv/email），
 * 與機台 B014 下發同源。未設定者一律略過（回 null），確保未開發票的機台零接觸。
 */
class EcpayInvoiceService
{
    private const REVISION = '3.2.9';

    private function baseUrl(): string
    {
        // fail-safe：一律預設「測試站」，只有明確設定 ECPAY_INVOICE_BASE_URL 才會打正式站。
        // 不可用 APP_ENV 判斷——demo 站的 APP_ENV 也是 production，靠它會誤打正式綠界開出真發票。
        // 正式環境必須在 .env 設 ECPAY_INVOICE_BASE_URL=https://einvoice.ecpay.com.tw；
        // 若漏設，最壞情況是正式環境開到測試站（無真實發票、可被察覺），不會誤開真發票。
        $configured = config('services.ecpay_invoice.base_url');
        if (!empty($configured)) {
            return rtrim($configured, '/');
        }
        return 'https://einvoice-stage.ecpay.com.tw';
    }

    /** 取得機台綠界發票設定；未設定回 null。 */
    public function configForMachine(Machine $machine): ?array
    {
        $settings = $machine->paymentConfig->settings ?? [];
        $merchantId = data_get($settings, 'ecpay_invoice.store_id');
        $hashKey = data_get($settings, 'ecpay_invoice.hash_key');
        $hashIv = data_get($settings, 'ecpay_invoice.hash_iv');

        if (empty($merchantId) || empty($hashKey) || empty($hashIv)) {
            return null;
        }

        return [
            'merchant_id' => $merchantId,
            'hash_key' => $hashKey,
            'hash_iv' => $hashIv,
            'email' => data_get($settings, 'ecpay_invoice.email', ''),
        ];
    }

    private function encrypt(array $data, string $key, string $iv): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return base64_encode(openssl_encrypt(urlencode($json), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv));
    }

    private function decrypt(?string $data, string $key, string $iv): array
    {
        if (empty($data)) {
            return [];
        }
        $decrypted = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return [];
        }
        return json_decode(urldecode($decrypted), true) ?: [];
    }

    /** 共用 POST：包 RqHeader + AES 加密 Data，回傳 decode 後的 Data 陣列（失敗回 null）。 */
    private function post(string $endpoint, array $cfg, array $payloadData): ?array
    {
        $body = [
            'MerchantID' => $cfg['merchant_id'],
            'RqHeader' => ['Timestamp' => time(), 'Revision' => self::REVISION],
            'Data' => $this->encrypt($payloadData, $cfg['hash_key'], $cfg['hash_iv']),
        ];

        try {
            $resp = Http::timeout(15)->asJson()->post($this->baseUrl() . $endpoint, $body);
            $json = $resp->json();
            if (!is_array($json) || !isset($json['Data'])) {
                Log::warning('ECPay invoice response missing Data', ['endpoint' => $endpoint, 'resp' => $json]);
                return null;
            }
            return $this->decrypt($json['Data'], $cfg['hash_key'], $cfg['hash_iv']);
        } catch (\Throwable $e) {
            Log::error('ECPay invoice request failed: ' . $e->getMessage(), ['endpoint' => $endpoint]);
            return null;
        }
    }

    /**
     * 查詢發票（GetIssue），以 RelateNumber 查。
     * 回傳 decode 後陣列（含 RtnCode/IIS_Number 等）或 null（金鑰缺/連線失敗）。
     */
    public function getIssue(Machine $machine, string $relateNumber): ?array
    {
        $cfg = $this->configForMachine($machine);
        if (!$cfg) {
            return null;
        }
        return $this->post('/B2CInvoice/GetIssue', $cfg, [
            'MerchantID' => $cfg['merchant_id'],
            'RelateNumber' => $relateNumber,
        ]);
    }

    /** 補開：以完整開立內容呼叫 Issue。回傳 decode 後陣列或 null。 */
    public function issue(Machine $machine, array $invoicePayload): ?array
    {
        $cfg = $this->configForMachine($machine);
        if (!$cfg) {
            return null;
        }
        $invoicePayload['MerchantID'] = $cfg['merchant_id'];
        return $this->post('/B2CInvoice/Issue', $cfg, $invoicePayload);
    }

    /**
     * 取得發票列印頁網址（InvoicePrint）。
     * 綠界回傳 Data.InvoiceHtml 為可列印頁 URL（自呼叫起 1 小時內有效）。
     * 回傳 decode 後陣列（含 RtnCode/InvoiceHtml）或 null（金鑰缺/連線失敗）。
     */
    public function invoicePrint(Machine $machine, string $invoiceNo, string $invoiceDate): ?array
    {
        $cfg = $this->configForMachine($machine);
        if (!$cfg) {
            return null;
        }
        return $this->post('/B2CInvoice/InvoicePrint', $cfg, [
            'MerchantID' => $cfg['merchant_id'],
            'InvoiceNo' => $invoiceNo,
            'InvoiceDate' => $invoiceDate,
            'PrintStyle' => 1,
            'IsShowingDetail' => 1,
        ]);
    }

    /** 作廢（Invalid）。 */
    public function invalid(Machine $machine, string $invoiceNo, string $invoiceDate, string $reason = '作廢'): ?array
    {
        $cfg = $this->configForMachine($machine);
        if (!$cfg) {
            return null;
        }
        return $this->post('/B2CInvoice/Invalid', $cfg, [
            'MerchantID' => $cfg['merchant_id'],
            'InvoiceNo' => $invoiceNo,
            'InvoiceDate' => $invoiceDate,
            'Reason' => mb_substr($reason ?: '作廢', 0, 20),
        ]);
    }

    /**
     * 由既有 Invoice（+ 關聯 Order/items）重建開立內容並補開。
     * 沿用 invoice.relate_number 當 RelateNumber（冪等，不會重複開立）。
     */
    public function reissue(Invoice $invoice): ?array
    {
        $machine = $invoice->machine;
        $cfg = $this->configForMachine($machine);
        if (!$cfg || empty($invoice->relate_number)) {
            return null;
        }

        $order = $invoice->order;
        $metadata = $invoice->metadata ?? [];
        $businessTaxId = $metadata['business_tax_id'] ?? '';
        $carrier = $invoice->carrier_id ?? '';
        $loveCode = $invoice->love_code ?? '';

        // 載具類型判定（對齊機台端邏輯）
        $carrierType = '';
        $donation = '0';
        if (strlen($loveCode) > 2) {
            $donation = '1';
            $carrierType = '';
        } elseif (strlen($carrier) === 8) {
            $carrierType = '3'; // 手機條碼
        } elseif (strlen($carrier) === 16) {
            $carrierType = '2'; // 自然人憑證
        } elseif (empty($businessTaxId)) {
            $carrierType = '1'; // 綠界會員載具
        }

        // 品項
        $items = [];
        $seq = 1;
        $orderItems = $order ? $order->items : collect();
        foreach ($orderItems as $oi) {
            $items[] = [
                'ItemSeq' => $seq++,
                'ItemName' => $oi->product_name ?: ('商品' . $oi->product_id),
                'ItemCount' => (int) $oi->quantity,
                'ItemWord' => '個',
                'ItemPrice' => (int) round($oi->price),
                'ItemTaxType' => '1',
                'ItemAmount' => (int) round($oi->subtotal ?: ($oi->price * $oi->quantity)),
                'ItemRemark' => '',
            ];
        }
        if (empty($items)) {
            // 無明細時以單列總額補上，確保發票可開立
            $items[] = [
                'ItemSeq' => 1,
                'ItemName' => '商品',
                'ItemCount' => 1,
                'ItemWord' => '式',
                'ItemPrice' => (int) round($invoice->amount),
                'ItemTaxType' => '1',
                'ItemAmount' => (int) round($invoice->amount),
                'ItemRemark' => '',
            ];
        }

        $payload = [
            'RelateNumber' => $invoice->relate_number,
            'CustomerID' => $machine->serial_no,
            'Print' => empty($businessTaxId) ? '0' : '1',
            'CustomerIdentifier' => $businessTaxId,
            'CustomerName' => $businessTaxId,
            'CustomerAddr' => $businessTaxId,
            'CustomerPhone' => '',
            'CustomerEmail' => $donation === '1'
                ? ($invoice->relate_number . substr($cfg['email'], strpos($cfg['email'], '@') ?: 0))
                : ($cfg['email'] ?: ''),
            'ClearanceMark' => '',
            'Donation' => $donation,
            'LoveCode' => $donation === '1' ? $loveCode : '',
            'CarrierType' => $carrierType,
            'CarrierNum' => ($carrierType === '2' || $carrierType === '3') ? str_replace('+', ' ', $carrier) : '',
            'TaxType' => '1',
            'SpecialTaxType' => 0,
            'SalesAmount' => (int) round($invoice->amount),
            'InvoiceRemark' => '發票備註:無',
            'InvType' => '07',
            'vat' => '1',
            'Items' => $items,
        ];

        return $this->issue($machine, $payload);
    }
}

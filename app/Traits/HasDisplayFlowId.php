<?php

namespace App\Traits;

/**
 * 提供「給人看的 flow_id」accessor：去掉機台序號前綴，顯示乾淨易讀的機台流水號。
 *
 * 資料庫仍儲存完整的 `serial_no . flow_id`（全域唯一值），供後台去重 / 冪等 / 對帳判斷使用；
 * 此 accessor 只用於畫面與匯出顯示，不改動任何 DB 值。
 *
 * flow_id 格式：serial_no . YYYYMMDDHHMMSSXXXX（無分隔符）。
 * 套用此 trait 的 Model 需具備 `flow_id` 欄位與 `machine`（含 serial_no）關聯。
 */
trait HasDisplayFlowId
{
    public function getDisplayFlowIdAttribute(): string
    {
        $flowId = (string) ($this->flow_id ?? '');
        $serial = $this->machine?->serial_no;

        // 僅在確實以該機台序號開頭時才剝除前綴；舊的未前綴資料原樣顯示（防禦）。
        if ($serial !== null && $serial !== '' && str_starts_with($flowId, $serial)) {
            return substr($flowId, strlen($serial));
        }

        return $flowId;
    }
}

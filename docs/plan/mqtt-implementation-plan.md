# Star Cloud MQTT 基礎架構實作計畫 (Phase 1)

本計畫旨在建立 Star Cloud 的高併發通訊基石，包含佈署 EMQX Broker、開發 Go MQTT Gateway，並建立與 Laravel 之間的 Redis **雙向**異步橋接機制。

---

## User Review Required

> [!IMPORTANT]
> **雙向通訊架構**：本計畫不只處理「機台 ➜ 雲端」的上報，也同時建立「雲端 ➜ 機台」的指令下發通道。後台管理員在按下「遠端出貨」按鈕時，Laravel 會將指令推入 Redis，由 Go Gateway 轉發至 EMQX，再即時送達機台 APP。

> [!WARNING]
> **資源配額**：Go Gateway 雖然輕量，但在 Docker 環境中仍建議設定 `mem_limit` 避免極端情況下的資源爭搶。

---

## 系統架構總覽

```
機台 Android APP
     │
     ├─ [上行] Publish ──→ EMQX ──→ Go Gateway ──→ Redis List (mqtt_incoming_jobs) ──→ Laravel mqtt:listen ──→ Job ──→ MySQL
     │
     └─ [下行] Subscribe ←── EMQX ←── Go Gateway ←── Redis List (mqtt_outgoing_commands) ←── Laravel MqttCommandService
```

---

## Proposed Changes

### 1. 基礎設施佈署 (Infrastructure)

#### [MODIFY] [compose.yaml](file:///home/mama/projects/star-cloud/compose.yaml)
- **新增 `emqx` 服務**：
    - Image: `emqx/emqx:5.10.3`（開源版最終穩定版，Apache 2.0 授權）
    - Ports: `1883:1883` (MQTT), `8083:8083` (WebSocket), `18083:18083` (Dashboard)
    - 加入 `sail` 網路
    - 配置 Redis Auth 插件環境變數，指向 `star-cloud-redis`
- **新增 `mqtt-gateway` 服務**：
    - 使用 `mqtt-gateway/Dockerfile` 進行 Multi-stage build
    - 連接至 `sail` 網路
    - 依賴 `emqx` 與 `redis`
    - 環境變數從 `.env` 讀取

#### [MODIFY] [.env](file:///home/mama/projects/star-cloud/.env)
- 新增以下環境變數：
```env
# MQTT / EMQX
MQTT_BROKER_HOST=emqx
MQTT_BROKER_PORT=1883
EMQX_DASHBOARD_PORT=18083

# Go Gateway
MQTT_GATEWAY_CLIENT_ID=star-cloud-gateway
MQTT_REDIS_HOST=star-cloud-redis
MQTT_REDIS_PORT=6379
```

---

### 2. Go MQTT Gateway 開發 (The Bridge)

#### [NEW] 完整目錄結構
```
mqtt-gateway/
├── Dockerfile              ← Multi-stage build (builder + alpine)
├── go.mod
├── go.sum
├── main.go                 ← 進入點：初始化、訊號監聽、graceful shutdown
├── config/
│   └── config.go           ← 讀取環境變數 (EMQX_ADDR, REDIS_ADDR 等)
├── internal/
│   ├── handler/
│   │   ├── heartbeat_handler.go    ← 處理 machine/+/heartbeat
│   │   ├── error_handler.go        ← 處理 machine/+/error
│   │   └── transaction_handler.go  ← 處理 machine/+/transaction
│   └── bridge/
│       ├── redis_consumer.go       ← [下行] BLPOP mqtt_outgoing_commands，轉發至 EMQX
│       └── redis_pusher.go         ← [上行] RPUSH mqtt_incoming_jobs
```

#### [上行邏輯] 機台 ➜ 雲端
1. 訂閱 `machine/+/heartbeat`, `machine/+/error`, `machine/+/transaction`。
2. 從 Topic 路徑提取 `serial_no`。
3. 包裝成 `BridgePayload`：
   ```json
   {
     "type": "heartbeat",
     "serial_no": "M-001",
     "payload": { "current_page": 1, "temperature": 25.5 },
     "received_at": "2026-04-14T09:00:00+08:00"
   }
   ```
4. 執行 `RPUSH mqtt_incoming_jobs {json}`。

#### [下行邏輯] 雲端 ➜ 機台 (新增)
1. Go Gateway 啟動一條 Goroutine，持續 `BLPOP mqtt_outgoing_commands`。
2. 取得 JSON 後解析目標 `serial_no` 與 `command` 內容。
3. Publish 至 `machine/{serial_no}/command` (QoS 1)。
   ```json
   {
     "target": "M-001",
     "command": "dispense",
     "payload": { "slot_no": 5, "transaction_id": "T202604140001" },
     "message_id": "MSG_123456789"
   }
   ```

#### [NEW] mqtt-gateway/Dockerfile
```dockerfile
# Stage 1: Build
FROM golang:1.23-alpine AS builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -o gateway .

# Stage 2: Run
FROM alpine:3.20
COPY --from=builder /app/gateway /usr/local/bin/gateway
CMD ["gateway"]
```

---

### 3. Laravel 端實作

#### [NEW] app/Console/Commands/ListenMqttQueue.php
- Artisan Command: `mqtt:listen`
- 使用 `BLPOP mqtt_incoming_jobs` 阻塞式監聽
- 根據 `type` 分派至對應的 Laravel Job：
    - `heartbeat` ➜ `ProcessHeartbeatJob`
    - `error` ➜ `ProcessMachineErrorJob`
    - `transaction` ➜ `ProcessTransactionJob`

#### [NEW] app/Services/Machine/MqttCommandService.php
- 提供 `sendCommand(string $serialNo, string $command, array $payload)` 方法
- 將指令 JSON 推入 Redis List `mqtt_outgoing_commands`
- 供 Controller 呼叫（例如後台管理員按下「遠端出貨」按鈕）

#### [NEW] app/Jobs/Machine/ (三個 Job)
- `ProcessHeartbeatJob.php`: 更新 `last_heard_at`、溫度、頁面碼
- `ProcessMachineErrorJob.php`: 寫入 `machine_logs`，觸發告警通知
- `ProcessTransactionJob.php`: 更新庫存、建立交易紀錄

#### [MODIFY] [MachineAuthController.php](file:///home/mama/projects/star-cloud/app/Http/Controllers/Api/V1/App/MachineAuthController.php)
- 在 B014 核發 `api_token` 後，同步寫入 Redis：
  `Redis::set("machine_auth:{$serial_no}", hash('sha256', $token));`
- Token 更新/撤銷時，同步刪除 Redis 中的對應 Key

---

## Architecture Decisions (Confirmed)

### ✅ EMQX 版本策略
- **統一鎖定**：開發與正式環境皆使用 `emqx/emqx:5.10.3`（開源版最後一個穩定版本）。
- **選擇理由**：EMQX 從 5.9.0 起合併為統一版本，6.x 改用 BSL 商業授權。`5.10.3` 是純開源 (Apache 2.0) 的最終穩定版，功能完整且免授權費用。

### ✅ 下行指令安全性（無需額外 OTP）
App 連線 MQTT 時已使用 `api_token` 作為密碼完成身份認證，因此 **MQTT 連線本身即為認證通道**。安全性由以下三層保障：
1. **連線層**：App 使用 `serial_no` + `api_token` 連線 EMQX，未通過驗證的裝置無法訂閱任何 Topic。
2. **ACL 層**：EMQX 存取控制確保只有 Go Gateway 能 Publish 到 `machine/{id}/command`，機台 App 無法偽造指令。
3. **冪等性層**：每條下行指令的 Payload 包含唯一的 `message_id`，App 端應記錄已執行的 ID，防止重複執行同一條指令。

> [!NOTE]
> **結論**：上行用 Token 當密碼、下行用 ACL 當門禁、`message_id` 當防重複鎖。三層防護已足夠，不需要額外的 OTP 機制。

---

## Verification Plan

### 1. 基礎設施驗證
- 執行 `./vendor/bin/sail up -d`
- 造訪 `http://localhost:18083` 確認 EMQX Dashboard 正常啟動
- 確認 Go Gateway Container 的 logs 顯示 "Connected to EMQX" 與 "Connected to Redis"

### 2. 上行通訊測試 (機台 ➜ 雲端)
- 使用 MQTTX 工具連接 `localhost:1883`
- 發送心跳 JSON 至 `machine/TEST-001/heartbeat`
- 檢查 Laravel 日誌確認 `mqtt:listen` 成功接收並分派 Job
- 檢查 MySQL `machines` 表的 `last_heard_at` 是否更新

### 3. 下行通訊測試 (雲端 ➜ 機台)
- 在 MQTTX 訂閱 `machine/TEST-001/command`
- 透過 Laravel Tinker 呼叫 `MqttCommandService::sendCommand('TEST-001', 'reboot', [])`
- 確認 MQTTX 收到 reboot 指令的 JSON

### 4. 壓力測試
- 使用 Go Script 模擬 500 台機台同時發送心跳
- 監控 Redis List 長度與 Laravel Worker 的處理速率

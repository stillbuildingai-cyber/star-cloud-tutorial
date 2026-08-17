# Star Cloud — IoT 智慧插座機隊管理平台（教學版）

這是一套**真正商用等級**的 IoT 機隊管理後台：Laravel + MySQL + Redis + EMQX(MQTT) + Go Gateway。
本教學系列會帶你從零把它在自己電腦上跑起來，接上一顆智慧插座（ESP32 或 Wemos D1 mini），
體驗一次完整的「裝置 → MQTT → 後台 → 資料庫 → 網頁介面」資料流。

> ⚠️ 這是**教學沙盒版本**：密碼都是明碼的預設值、MQTT 沒開 TLS、EMQX 也沒有對外防護。
> 這樣設計是為了讓新手能專心學架構、不用先搞懂資安設定。
> **正式上線的環境一定要換掉所有密碼、開啟 TLS 與 MQTT 認證**，這些我們會在後面「上線維運篇」另外教。

---

## 這套系統長什麼樣子

```
┌─────────────┐        MQTT         ┌──────────┐        ┌─────────────────┐
│ 智慧插座裝置 │ ───────────────────▶│  EMQX    │───────▶│  Go MQTT Gateway │
│ (ESP32/D1)  │◀─────────────────── │ (Broker) │        └────────┬─────────┘
└─────────────┘      下發指令        └──────────┘                 │ 寫入佇列
                                                                    ▼
┌─────────────┐        瀏覽器        ┌──────────┐        ┌─────────────────┐
│   你 (瀏覽器) │ ───────────────────▶│ Laravel  │◀───────│      Redis       │
└─────────────┘                     │  (後台)   │  消費   └─────────────────┘
                                     └────┬─────┘
                                          ▼
                                     ┌──────────┐
                                     │  MySQL   │
                                     └──────────┘
```

| 服務 | 是什麼 | 本機 Port |
|---|---|---|
| `laravel.test` | 後台網頁與 API（PHP/Laravel） | 8090 |
| `mysql` | 資料庫 | 3306 |
| `redis` | 佇列與 MQTT 認證資料 | 6380 |
| `emqx` | MQTT Broker，裝置實際連線的對象 | 1883（MQTT）、18083（管理介面） |
| `mqtt-gateway` | 訂閱 MQTT、轉發進 Redis 佇列（Go 寫的） | 無對外 port |

裝置不會直接跟 Laravel 講話——它只認識 MQTT。Laravel 是透過 Redis 佇列，由 `mqtt-gateway`
把裝置的訊息「翻譯」過來的。這個中間層的存在，是這套架構能撐住大量裝置同時連線的關鍵，也是這系列教學想讓你真正學到的東西。

---

## 前置需求

只需要兩個東西，不需要事先裝 PHP、MySQL 或任何開發環境：

1. **[Docker Desktop](https://www.docker.com/products/docker-desktop/)**（Windows/Mac）或 Docker Engine（Linux）
2. **Git**

裝好 Docker Desktop 後，打開它，確認左下角顯示是綠色的「Running」再繼續下一步。

---

## 快速開始

### 1. 下載專案

```bash
git clone <這個 repo 的網址>
cd star-cloud-tutorial
```

### 2. 複製環境設定檔

```bash
cp .env.example .env
```

`.env.example` 裡已經是可以直接用的教學預設值，不用改任何東西就能跑起來。

### 3. 安裝 PHP 套件（第一次跑，之後不用重複）

因為專案還沒有 `vendor/` 資料夾（PHP 套件），要先用一個一次性的容器把它裝起來：

```bash
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html composer:latest \
  composer install --ignore-platform-reqs
```

> Windows 使用者：請用 Git Bash 或 WSL 執行這些指令，不要用 CMD/PowerShell 原生視窗，
> `$(pwd)` 這種語法在 PowerShell 裡意義不同會出錯。

> ⚠️ **如果看到大量 `HTTP/2 429` 錯誤裝不完**：這是 composer 在跟 GitHub 要套件時，
> 撞到 GitHub **未登入請求**的流量限制（每小時 60 次，一次全新安裝隨便就會用掉上百次）。
> 常發生在公司/學校共用對外 IP，或短時間內重跑好幾次安裝的情況。解法（任選一種）：
>
> - **最簡單**：等 10–60 分鐘讓額度重置，直接重跑同一行指令即可（已下載成功的套件會跳過，不用整個重來——
>   但如果每次都用 `docker run --rm` 且沒有掛快取目錄，重試也沒用，見下面第二點）
> - **加速重試**：幫 composer 的下載快取掛一個持久化目錄，這樣重試只需要抓還沒抓到的部分：
>   ```bash
>   mkdir -p ~/.composer-cache
>   docker run --rm -v "$(pwd):/var/www/html" -v ~/.composer-cache:/tmp/cache \
>     -w /var/www/html composer:latest composer install --ignore-platform-reqs
>   ```
> - **一勞永逸**：申請一組免費的 [GitHub Personal Access Token](https://github.com/settings/tokens)（不用勾任何權限），
>   把上面指令加一段 `-e COMPOSER_AUTH='{"github-oauth":{"github.com":"你的token"}}'`，
>   額度會從 60 次/小時提升到 5000 次/小時，通常一次就能裝完。

### 4. 建置前端資源（第一次跑，之後不用重複）

跟第 3 步一樣的原因，還沒有 `node_modules` 跟編譯好的 `public/build`，一樣用一次性容器處理：

```bash
docker run --rm -v "$(pwd):/app" -w /app node:22-slim bash -c "npm install && npm run build"
```

> ⚠️ 這步沒做的話，網頁能開但**登入頁會直接噴 `ViteManifestNotFoundException`**，
> 錯誤訊息會說 `Vite manifest not found at: /var/www/html/public/build/manifest.json`。
> 看到這個錯誤，代表就是漏了這一步。

### 5. 啟動所有服務

```bash
docker compose up -d --build
```

第一次啟動會下載 image、編譯 Go Gateway，需要幾分鐘，泡杯茶等一下。

### 6. 初始化資料庫

```bash
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
docker compose exec laravel.test php artisan mqtt:sync-auth
```

這三行分別做：產生應用程式加密金鑰、建表 + 灌入示範資料（含一台示範插座機台）、
把機台的 MQTT 帳密同步到 Redis（不做這步，裝置會連線被拒絕）。

### 7. 啟動 MQTT 訊息監聽（背景常駐）

```bash
docker compose logs -f mqtt-worker
```

如果看到持續有輸出且沒有 Error，代表 `mqtt:listen` 這個常駐程式已經在運作了（它是 `compose.yaml`
裡 `laravel.mqtt` 服務啟動時就自動執行的，不用你手動下指令）。

---

## 驗證安裝成功

1. **後台網頁**：瀏覽器開 http://localhost:8090，帳號 `admin`、密碼 `password`
2. **EMQX 管理介面**：瀏覽器開 http://localhost:18083，帳號 `admin`、密碼 `public`
3. 後台的「機台管理」應該會看到一台**教學示範智慧插座**（序號 `SW-DEMO-001`），狀態是離線——這是正常的，因為還沒有真的裝置連上來

---

## 示範機台帳密（用來連線測試）

初始化時已經自動建立一台示範機台，MQTT 連線資訊如下：

| 項目 | 值 |
|---|---|
| 序號 (MQTT username) | `SW-DEMO-001` |
| API Token (MQTT password) | `tutorial-demo-token` |
| MQTT Host | `localhost` |
| MQTT Port | `1883` |

### 沒有實體裝置也能測：模擬心跳

```bash
bash simulate-heartbeat.sh SW-DEMO-001 tutorial-demo-token
```

跑起來後回到後台「機台管理」重新整理，示範機台應該會變成「上線」狀態。

### 接上真正的硬體

如果你在跟著〈韌體篇〉燒錄 ESP32 或 Wemos D1 mini，把裝置的中央後台位址設定成：
`ws://<你電腦的區網 IP>:1883`，序號/Token 就填上面這組，或是自己在後台「機台管理」新增一台。

---

## 常用指令

| 功能 | 指令 |
|---|---|
| 啟動 | `docker compose up -d` |
| 停止 | `docker compose down` |
| 看 log | `docker compose logs -f <服務名稱>` |
| 進 Laravel 容器下指令 | `docker compose exec laravel.test php artisan <指令>` |
| 重新同步 MQTT 認證 | `docker compose exec laravel.test php artisan mqtt:sync-auth` |
| 完全重來（清空資料庫重建） | `docker compose down -v && docker compose up -d` 後重跑步驟 6 |

---

## 目錄結構

- `app/Http/Controllers/`：後台控制器
- `app/Models/Machine/Machine.php`：機台資料模型，MQTT 認證自動同步的邏輯就在這裡
- `app/Services/Machine/MachineService.php`：`syncMqttAuth()` 在這裡，值得精讀
- `app/Console/Commands/`：`mqtt:listen`、`mqtt:sync-auth` 這兩個指令
- `mqtt-gateway/`：Go 寫的 MQTT ↔ Redis 橋接服務，獨立的小專案
- `database/seeders/SmartSocketDemoSeeder.php`：示範機台的種子資料
- `docs/`：這個系列教學會用到的補充文件

> 這套後台其實遠不只智慧插座——它原本是設計給整個機隊（含各種商用機台）用的通用平台，
> 所以程式碼裡會看到很多跟插座無關的東西。這系列教學只會聚焦在跟 Machine／MQTT 相關的部分，
> 其餘的先不用管，看到不懂的命名或功能屬於正常現象。

---

## 授權

© Star Cloud. 這份教學版原始碼供課程學員學習使用。

# 會員系統（Members）功能說明

> 此文件記錄會員系統的設計決策與功能說明，供開發與維護時參閱。

---

## 概述

會員系統用於智能販賣機商城，支援消費者透過多種社群管道（Line、Google、Facebook）加入會員。

**重要區分**：
- `users` 表：後台管理員登入帳號
- `members` 表：前台消費者會員帳號

兩者**完全獨立**，無關聯。

---

## 資料表

### 1. `members` - 會員資料

| 欄位 | 類型 | 說明 |
|------|------|------|
| `id` | bigint | 主鍵 |
| `uuid` | string | 唯一識別碼（對外使用） |
| `name` | string | 姓名 |
| `email` | string | 電子郵件（可空） |
| `phone` | string | 手機號碼（可空） |
| `password` | string | 密碼（社群登入可空） |
| `birthday` | date | 生日 |
| `gender` | enum | 性別 |
| `avatar` | string | 頭像 URL |
| `is_active` | boolean | 是否啟用 |
| `email_verified_at` | timestamp | Email 驗證時間 |

### 2. `social_accounts` - 社群帳號

| 欄位 | 類型 | 說明 |
|------|------|------|
| `id` | bigint | 主鍵 |
| `member_id` | bigint | 關聯會員 |
| `provider` | enum | line / google / facebook |
| `provider_id` | string | 社群平台用戶 ID |
| `access_token` | text | 存取令牌 |
| `refresh_token` | text | 刷新令牌 |
| `profile_data` | json | 社群個人資料 |
| `token_expires_at` | timestamp | 令牌到期時間 |

### 3. `member_wallets` - 會員錢包

| 欄位 | 類型 | 說明 |
|------|------|------|
| `member_id` | bigint | FK，唯一 |
| `balance` | decimal | 儲值餘額 |
| `bonus_balance` | decimal | 回饋金餘額 |

### 4. `wallet_transactions` - 錢包交易

| 欄位 | 類型 | 說明 |
|------|------|------|
| `type` | enum | deposit/consume/refund/bonus/adjust |
| `amount` | decimal | 異動金額 |
| `balance_after` | decimal | 異動後餘額 |
| `reference_type/id` | | 關聯訂單或活動 |

### 5. `deposit_bonus_rules` - 儲值回饋規則

設定儲值達指定金額可獲得的回饋（固定金額或百分比）。

### 6. `member_points` - 點數帳戶

| 欄位 | 類型 | 說明 |
|------|------|------|
| `available_points` | int | 可用點數 |
| `pending_points` | int | 待生效點數 |
| `expired_points` | int | 已過期（統計） |
| `used_points` | int | 已使用（統計） |

### 7. `point_transactions` - 點數異動

| 欄位 | 類型 | 說明 |
|------|------|------|
| `type` | enum | earn/use/expire/gift/adjust |
| `points` | int | 異動點數 |
| `expires_at` | datetime | **此筆點數到期日** |

> 每筆獲得點數都記錄 `expires_at`，排程任務定期處理過期。

### 8. `point_rules` - 點數規則

設定消費/儲值/註冊等行為可獲得的點數及有效天數。

### 9. `membership_tiers` - 會員等級

| 欄位 | 類型 | 說明 |
|------|------|------|
| `name` | string | 等級名稱 |
| `annual_fee` | decimal | 年費（0=免費） |
| `discount_rate` | decimal | 折扣比例 |
| `point_multiplier` | decimal | 點數倍率 |

### 10. `member_memberships` - 會員等級紀錄

記錄會員的等級歸屬及有效期間。

### 11. `gift_definitions` - 禮品定義

| 欄位 | 類型 | 說明 |
|------|------|------|
| `type` | enum | points/coupon/product/discount/cash |
| `trigger` | enum | register/birthday/annual/upgrade/manual |

### 12. `member_gifts` - 禮品發放紀錄

記錄發放給會員的禮品及領取狀態。

---

## ER 關係圖

```
members
├── social_accounts (1:N)
├── member_wallets (1:1)
│   └── wallet_transactions (1:N)
├── member_points (1:1)
│   └── point_transactions (1:N)
├── member_memberships (1:N)
│   └── membership_tiers
└── member_gifts (1:N)
    └── gift_definitions
```

---

## 登入流程

```
使用者選擇社群登入
       ↓
取得 provider + provider_id
       ↓
查詢 social_accounts
       ↓
  ┌────┴────┐
已綁定      未綁定
  ↓           ↓
取得 member  建立新 member + social_account
  ↓           ↓
     完成登入
```

---

## Email 驗證（可選功能）

若需要 Email 驗證，需設定 `.env` 的 SMTP 並讓 `Member` Model 實作 `MustVerifyEmail`。

社群登入時自動標記 `email_verified_at`，僅對手機/密碼註冊要求驗證。

---

## API 端點

| Method | Endpoint | 說明 | 認證 |
|--------|----------|------|------|
| POST | `/api/members/register` | 註冊會員 | 否 |
| POST | `/api/members/login` | 登入 | 否 |
| POST | `/api/members/social-login` | 社群登入 | 否 |
| GET | `/api/members/profile` | 取得個人資料 | 是 |
| PUT | `/api/members/profile` | 更新個人資料 | 是 |
| POST | `/api/members/logout` | 登出 | 是 |

---

## Postman 測試

匯入：`docs/postman/Star_Cloud_Members_API.postman_collection.json`

---

## 社群登入實測

訪問 `/test/social-login` 測試 Google/Line 登入。

---

## 開發進度

| 日期 | 項目 | 狀態 |
|------|------|------|
| 2026-01-12 | 會員核心 (members, social_accounts) | ✅ 完成 |
| 2026-01-12 | 錢包系統 (3 表 + 3 Model) | ✅ 完成 |
| 2026-01-12 | 點數系統 (3 表 + 3 Model) | ✅ 完成 |
| 2026-01-12 | 年度會員 (2 表 + 2 Model) | ✅ 完成 |
| 2026-01-12 | 贈送機制 (2 表 + 2 Model) | ✅ 完成 |


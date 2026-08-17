# Star Cloud 多租戶與權限架構 (Multi-Tenant RBAC) 實作規劃

## 1. 架構目標
為了支援 Star Cloud 系統具備 B2B SaaS 的多租戶特性，讓「系統管理員（內部營運）」與「租戶（客戶公司）」能在同一套後台中共存。各租戶能自行建立子帳號並分配權限，且資料彼此絕對隔離。

將導入基於 `companies` 的租戶隔離，並結合 `spatie/laravel-permission` 實作多租戶的角色權限控制。

---

## 2. 核心實作架構

### 2.1 租戶隔離層 (Tenant Isolation)
建立實體的租戶邊界，確保每個客戶在物理與邏輯上只能存取自己的資料。
*   **變更**：新增 `companies` 表（公司名稱、統編、狀態等）。
*   **綁定**：在 `users`, `machines`, 等核心資源表中加上 `company_id`。
*   **身份判定**：
    *   `company_id = null`：系統預設管理員（內部工程師 / 營運人員），具備跨公司存取能力。
    *   `company_id = N`：客戶端帳號（租戶）。

### 2.2 角色與權限層 (Roles & Permissions)
利用 `spatie/laravel-permission` 結合多租戶。
*   **全域權限 (Permissions)**：定義系統所有的最小操作單位（如 `machine.view`, `user.create`），所有租戶共用權限定義。
*   **多租戶角色 (Tenant-Aware Roles)**：原本套件的 `roles` 表必須擴充加上 `company_id` 欄位。
    *   系統預設角色 (`company_id = null`)：如 Super Admin。
    *   客戶自建角色 (`company_id = N`)：如客戶自定義的「台北區維修員」，只能分配給統一個 `company_id` 的使用者。

### 2.3 流程控制與防禦 (Flow Control)
*   **自動過濾 (Global Scopes)**：寫一個 Trait `TenantScoped`，當非內部人員登入時，自動幫 Eloquent Model 的所有查詢加上 `where('company_id', auth()->user()->company_id)`。
*   **越權防禦 (Create User)**：租戶在後台建立「新子帳號」時，API Controller 必須在背景強制 `NewUser->company_id = Auth::user()->company_id`。
*   **角色分配防禦**：客戶分配角色給員工時，只能撈取並指定 `company_id` 與自己相同的 Role。

---

## 3. 實作步驟 (對應 Phase 1 與 Phase 3)

### 步驟 1：資料庫結構 (Migrations)
1. 建立 `create_companies_table` migration。
2. 建立 `add_company_id_to_users_table` migration。
3. 調整 `machines` 及其他資源表，加入 `company_id` 欄位與外鍵。
4. 安裝並發布 `spatie/laravel-permission` migrations，並接續寫一個 migration 為 `roles` 和 `permissions` 調整結構 (加入 `company_id` 至 `roles`)。

### 步驟 2：Eloquent Models 與 Scopes 修改
1. 建立 `Company` Model (`App\Models\System\Company`)。
2. 修改 `User` Model (`App\Models\Member\User`)，加入 `company()` 關聯與 Helper methods (`isSystemAdmin()`, `isTenant()`)。
3. 建立 `TenantScoped` Trait，包含 Laravel Global Scope 邏輯。套用至 `Machine` 等需要隔離的 Model。

### 步驟 3：後端流程與介面防禦 (Middleware & Controllers)
1. 建立 `CheckTenantAccess` Middleware 進行基礎的路由存取權限卡控。
2. 在後台的「帳號與權限」模組中，實作過濾邏輯：
    *   撈取使用者、角色列表時帶入 `company_id`。
    *   創建或修改時強制綁定 `Auth::user()->company_id`。

### 步驟 4：前端 Blade 呈現切換
1. 使用 Blade 的 `@if(auth()->user()->isSystemAdmin())` 在 Sidebar 中隱藏或顯示「全站管理」、「合約設定」等專屬區塊。
2. 確保一般租戶登入時，左側選單僅顯示其有權限存取的「機台管理」、「我的專屬會員」、「所屬帳號設定」等區塊。

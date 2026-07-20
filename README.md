# DDDTest - 羽球微服務專案 (Badminton Microservices Project)

本專案是一個基於領域驅動設計 (DDD) 架構設計的微服務專案，採用 PHP Laravel 11 與 Docker 進行開發與部署。

## 系統架構簡介
* **gateway**: API 閘道器 (Nginx)，對外監聽 `8080` 埠。
* **user-service**: 用戶與權限服務，監聽內部 `8000` 埠。
* **order-service**: 訂單服務，監聽內部 `8000` 埠。
* **基礎設施**:
  * **mariadb**: 關聯式資料庫 (包含 `order_db` 與 `user_db`)。
  * **redis**: 快取資料庫。
  * **rabbitmq**: 訊息佇列服務。

---

## 專案資料夾結構 (Project Folder Structure)

本專案採用微服務架構與領域驅動設計 (Domain-Driven Design, DDD) 的核心思想。以下為專案的完整目錄結構與說明：

### 1. 根目錄結構 (Root Directory)

```text
DDDTest/ (專案根目錄)
├── gateway/                 # API Gateway (Nginx 反向代理與統一路由分流)
├── infrastructure/          # 共享基礎設施設定 (MariaDB, Redis 等服務配置)
│   ├── mariadb/             # MariaDB 容器的 Dockerfile、my.cnf 設定與資料庫初始化腳本 (init-db.sql)
│   └── redis/               # Redis 快取服務相關設定 (預留)
├── services/                # 微服務主目錄 (各服務高內聚、低耦合)
│   ├── user-service/        # 用戶與權限微服務 (PHP Laravel 11)
│   └── order-service/       # 訂單微服務 (PHP Laravel 11)
├── shared/                  # 共享模組與通用套件
│   └── common/              # 本地共享 PHP 套件 (ddd-test/common，供各微服務 Symlink 載入)
├── docs/                    # 專案相關設計文件與配置備份
├── docker-compose.yml       # 應用服務層 (Services & Gateway) 的 Docker Compose 設定
└── docker-compose.infra.yml # 基礎設施層 (Database, Cache) 的 Docker Compose 設定
```

### 2. 微服務內部架構 (以 Laravel DDD 設計為例)

各微服務（如 `user-service`, `order-service`）內部的 `app/` 目錄遵循 DDD 設計原則進行組織：

```text
services/user-service/app/ (或 order-service/app/)
├── Domains/                 # 領域層 (Domain Layer) - 系統的核心業務邏輯與規則
│   └── User/                # 具體的領域 (例如 User, Venue, Order)
│       ├── Models/          # 領域模型 (Domain Entities/Eloquent Models)
│       └── Services/        # 領域服務 (Domain Services, 負責處理跨模型的業務邏輯)
├── Http/                    # 介面與應用層 (Interface & Application Layer)
│   ├── Controllers/         # API 控制器 (負責接收請求並呼叫領域服務)
│   ├── Middleware/          # 中間件 (如驗證、請求處理)
│   └── Requests/            # 表單驗證與請求資料處理 (Form Requests)
├── Infrastructure/          # 基礎設施層 (Infrastructure Layer)
│   └── Clients/             # 外部服務客戶端 (例如呼叫其他微服務的 API Client)
└── Providers/               # 服務提供者 (Service Providers, 綁定介面與註冊依賴)
```

### 3. 微服務其他目錄與檔案說明 (以 user-service 為準)

除核心業務邏輯所在的 `app/` 目錄外，各微服務（以 `user-service` 為例）亦包含 Laravel 11 框架運作、配置與部署所需的相關目錄與檔案：

#### 目錄說明 (Directories)
* **[bootstrap](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/bootstrap)**：框架啟動與初始化設定。包含 [app.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/bootstrap/app.php) 用於設定路由路徑、註冊自訂的中介軟體別名（例如：`role`、`permission` 等權限控制），以及設定全域異常處理。
* **[config](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/config)**：存放環境與應用程式的配置檔。由於 Laravel 11 進行了配置簡化，此處僅存放專案客製化的設定（如 `database.php` 資料庫連線配置、自訂的 `apiResponse.php` 及 `apiMessage.php` 等 API 統一回傳格式與訊息定義）。
* **[database](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/database)**：資料庫相關檔案目錄。包含 `migrations/`（定義資料庫結構變更的遷移檔）及 `seeders/`（用於初始化權限、角色以及產生預設管理員帳號的資料填充檔）。
* **[public](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/public)**：微服務的 Web 進入點與靜態檔案目錄。包含 `index.php`（所有 HTTP 請求的單一入口）及 `.htaccess` 等伺服器設定。
* **[routes](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/routes)**：路由定義目錄。包含 `api.php`（定義 API 路由端點）、`web.php`（基本網頁路由）與 `console.php`（排程或自訂 Artisan 命令路由）。
* **[storage](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/storage)**：系統運行產生的暫存與儲存目錄。包含 `logs/laravel.log`（系統執行日誌）、`framework/`（編譯後的 Blade 樣板、Session、快取等暫存檔案）。
* **[vendor](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/vendor)**：存放由 Composer 安裝的第三方依賴套件（如 Laravel 核心、Spatie Permission 等相依套件）。

#### 重要檔案说明 (Key Files)
* **`.env` / `.env.example`**：微服務專屬的環境變數設定檔（如資料庫連線資訊、Redis 配置等）。
* **`Dockerfile`**：用於建置該微服務容器映像檔的設定，包含 PHP 執行環境、擴充套件安裝以及工作目錄配置。
* **`artisan`**：Laravel 的命令列工具，用於執行遷移（`migrate`）、資料填充（`db:seed`）、清除快取等操作。
* **`composer.json` / `composer.lock`**：微服務獨立的 PHP 套件依賴管理檔案。

### 4. 資料夾功能詳細說明

* **[gateway](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/gateway)**：
  作為整個微服務系統的單一入口。使用 Nginx 進行路由分流，並利用 `auth_request` 機制將保護路由（如 `/api/orders`）的請求導向 `user-service` 進行 Token 驗證，通過後才轉發至對應的微服務。
* **[infrastructure](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/infrastructure)**：
  管理本地開發與部署所需的共享基礎設施配置。例如 `mariadb/init-db.sql` 用於在容器首次啟動時自動建立多個微服務所需的獨立資料庫 (`user_db` 與 `order_db`)，體現 "Database-per-Service" 的自治設計。
* **[services](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services)**：
  存放各自獨立的微服務，每個服務都包含獨立的運行環境 (`Dockerfile`)、相依套件管理 (`composer.json`) 與設定檔。服務間不直接共享資料庫，而是透過 HTTP API 或 API Gateway 進行通訊。
* **[shared](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared)**：
  跨微服務共享的通用程式碼與本地 Composer 套件目錄。包含通用 API 回應工具（`ApiResponder`）、通用服務結果（`ServiceResult`）、閘道器請求驗證中介軟體（`HeaderAuthMiddleware`）、微服務間的 HTTP 用戶端（`UserClient`）以及**通用繁體中文驗證語系檔（`validation.php`）**。透過 Composer 的 Path Repository 功能，以軟連結（Symlink）形式載入到各微服務中，實現高複用性與強類型邊界。
* **[docs](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/docs)**：
  存放開發團隊的設計文件、環境變數範本以及 Docker 設計的參考說明。
---

## 共享套件開發與使用須知 (Shared Package Development Guide)

本專案使用 `shared/common` 目錄作為跨微服務共用的 PHP 套件（套件名稱為 `ddd-test/common`）。為了確保開發順暢，請遵循以下開發須知：

### 1. 運作原理 (Symlink)
* **軟連結機制**：當在微服務執行 `composer install/update` 後，Composer 會自動在微服務的 `vendor/ddd-test/common` 目錄建立軟連結（Symlink）指向專案根目錄的 `shared/common`。
* **即時生效**：在 `shared/common/src` 內修改任何 `*.php` 檔案的程式碼，各微服務中均會**即時同步生效**，無須重新執行 `composer update` 或複製檔案。

### 2. 容器開發注意事項
* **Docker 卷掛載**：為了讓執行中的 Docker 容器能正確解析並存取宿主機（Host）上的共享程式碼，`docker-compose.yml` 中必須掛載 `./shared:/shared` 卷。
* **修改 `composer.json` 元件描述**：若修改了 `shared/common/composer.json`（例如新增套件依賴、新增 Autoload 映射、修改 `laravel.providers` 等），必須在微服務容器中手動更新 Composer 鎖定檔：
  ```bash
  docker exec -it <container_name> composer update ddd-test/common
  ```
  *(微服務容器名稱為 `dddtest-user-service-1` 與 `dddtest-order-service-1`)*。

### 3. Laravel Package Discovery 緩存清理
* 本共享套件透過 `Shared\Providers\SharedCommonServiceProvider` 自動載入並合併預設的 `apiResponse`、`apiMessage` 配置，以及繁體中文驗證語系檔（`validation.php`）。
* 若發生新註冊的類別或套件無法發現的問題，可能是 Laravel 的框架引導快取所致，請在容器中執行以下指令清理快取：
  ```bash
  # 1. 刪除快取檔案
  docker exec -it <container_name> rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
  # 2. 重新觸發套件偵測
  docker exec -it <container_name> php artisan package:discover
  ```

### 4. 統一設定與自訂覆寫
* **預設配置**：`shared/common/config` 下的 [apiResponse.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared/common/config/apiResponse.php) 與 [apiMessage.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared/common/config/apiMessage.php) 是全系統的通用預設值。套件內 `shared/common/lang/zh_TW/validation.php` 則為繁體中文驗證語系的通用基底。
* **自訂覆寫**：微服務若有特殊客製化需求，可在其本地對應目錄（如 `user-service/config/` 或 `user-service/lang/`）下建立同名檔案，Laravel 在加載時會**優先採用並覆寫**本地的設定，而未覆寫的鍵值將自動採用共享套件的預設配置。

#### 驗證語系 (Validation) 使用與開發說明：
1. **啟用中文驗證語系**：請確保在各微服務根目錄的 `.env` 檔案中已加入環境變數：
   ```env
   APP_LOCALE=zh_TW
   ```
2. **共用驗證規則與欄位翻譯**：
   * 當 FormRequest 驗證失敗時，Laravel 會自動套用共享套件 [validation.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared/common/lang/zh_TW/validation.php) 中的中文模板（如：`:attribute 欄位必填。`）。
   * 常用欄位對照已統一管理在 `attributes` 區塊（例如 `account` ➡️ `帳號`、`password` ➡️ `密碼`、`email` ➡️ `電子郵件`）。
3. **新增或覆寫欄位翻譯 (Attributes)**：
   * **全局新增**：若新增的欄位為跨服務通用，請直接修改共享套件的 [validation.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared/common/lang/zh_TW/validation.php) 中的 `attributes` 陣列。
   * **本地覆寫**：若新增的欄位僅適用於特定服務（如 `order-service` 專屬的 `course_id`），請在該微服務根目錄下建立 `lang/zh_TW/validation.php`，僅宣告需要的屬性對照即可。例如：
     ```php
     <?php
     return [
         'attributes' => [
             'course_id' => '課程識別碼',
         ],
     ];
     ```
     本套件已自訂載入器 `MixedFileLoader`，會自動將其與共享套件的預設語系進行深度合併，不需要複製整個檔案。

---

## 啟動步驟 (Docker Compose)

請依序執行以下步驟以逆序啟動專案：

### 1. 啟動基礎設施層 (Infrastructure Layer)
首先，啟動資料庫、快取與訊息佇列等基礎設施，這會自動建立共享的橋接網路 `dddtest-network`：
```bash
docker compose -f docker-compose.infra.yml up -d
```

### 2. 啟動應用服務層 (Application Layer)
接著，啟動微服務與 API Gateway：
```bash
docker compose up -d
```

### 3. 執行資料庫遷移與資料填充 (Database Migration & Seeding)
服務啟動後，需要初始化各微服務的資料庫表與預設資料：

* **User Service 遷移與資料填充**（建立用戶、權限表，並產生預設系統管理員帳號）：
  ```bash
  docker exec dddtest-user-service-1 php artisan migrate --seed
  ```
* **Order Service 遷移**（建立訂單表）：
  ```bash
  docker exec dddtest-order-service-1 php artisan migrate
  ```

---

## 測試連線與 API 路由

啟動後，API 統一透過 Gateway `http://localhost:8080` 進行存取。

### 1. 登入取得 Token (Login)
* **請求方式**：`POST`
* **網址**：`http://localhost:8080/api/auth/login`
* **Headers**：
  * `Content-Type`: `application/json`
  * `Accept`: `application/json`
* **Body (JSON)**：
  ```json
  {
      "account": "admin",
      "password": "password"
  }
  ```
* **說明**：登入成功後，回傳資料中的 `access_token` 用於後續保護路由的驗證。

### 2. 新增訂單 (POST Orders)
* **請求方式**：`POST`
* **網址**：`http://localhost:8080/api/orders`
* **Headers**：
  * `Authorization`: `Bearer <您的_access_token>`
  * `Content-Type`: `application/json`
  * `Accept`: `application/json`
* **Body (JSON)**：
  ```json
  {
      "course_id": 401,
      "amount": 5000,
      "status": "pending"
  }
  ```

### 3. 取得訂單列表 (GET Orders)
* **請求方式**：`GET`
* **網址**：`http://localhost:8080/api/orders`
* **Headers**：
  * `Authorization`: `Bearer <您的_access_token>`

---

## 使用套件說明 (Installed Packages)

本專案在各個服務與共享模組中引入了多個成熟的開源套件，以支撐系統的安全、通訊與審計需求：

### 1. 共享模組 (`shared/common`)
* **[php-amqplib/php-amqplib](https://github.com/php-amqplib/php-amqplib)**:
  * **用途**：RabbitMQ 的純 PHP 協定實作。
  * **說明**：專案將其封裝於 [RabbitMQService](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/shared/common/src/Infrastructure/Messaging/RabbitMQService.php) 中，提供高階的 Publish / Consume 介面，支撐微服務之間的非同步事件傳遞（例如 `order-service` 建立訂單後通知 `user-service`）。

### 2. 用戶服務 (`user-service`)
* **[spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)**:
  * **用途**：自動與手動記錄 Eloquent Model 變更與 API 操作日誌。
  * **說明**：用於實作 API 審計日誌（Audit Log）與用戶操作追蹤。目前配置了 [ApiActivityLog](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/app/Http/Middleware/ApiActivityLog.php) 中介軟體，能自動過濾敏感資訊並紀錄重要寫入 API（如新增管理員、升級教練等）的 IP、Payload 及狀態碼。過濾與排除規則可於 [api_log.php](file:///C:/Users/LIN/Desktop/DLH/project/DDDTest/services/user-service/config/api_log.php) 進行調整。
* **[spatie/laravel-permission](https://github.com/spatie/laravel-permission)**:
  * **用途**：角色與權限管理 (RBAC)。
  * **說明**：用於管理後台人員的權限（例如 `backend_user.manage` 權限）以及區分角色等級（如 `System Admin`）。
* **[laravel/passport](https://github.com/laravel/passport)** 與 **[laravel/sanctum](https://github.com/laravel/sanctum)**:
  * **用途**：API 憑證與認證機制。
  * **說明**：提供微服務架構中用戶認證 Token 的簽發與驗證機制。

---

## 常見問題排除 (Troubleshooting)

### Q1: 資料庫連線失敗 (`Name or service not known`)
如果 `user-service` 或 `order-service` 丟出無法連線至 `mariadb` 的錯誤，可能是 MariaDB 容器在 Docker 網路重啟時斷開了與共享網路 `dddtest-network` 的連線。

請手動執行以下指令將 MariaDB 重新連回網路並設定別名：
```bash
# 1. 斷開連線 (若有殘留舊連線)
docker network disconnect dddtest-network dddtest-mariadb-1

# 2. 重新連線並設定別名
docker network connect --alias mariadb dddtest-network dddtest-mariadb-1
```
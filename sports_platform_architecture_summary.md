# U-Badminton 運動平台：架構設計與開發規劃整理

此文件整理自您提供的對話記錄，該討論圍繞於如何為「**U-Badminton 運動平台**」設計一個便於未來拆分為獨立微服務 (Microservices) 的單一程式庫 (Monorepo) 架構。

---

## 核心技術棧
*   **後端框架**：Laravel 12 / PHP
*   **資料庫**：MariaDB (多資料庫獨立設計)
*   **容器化與編排**：Docker + Docker Compose
*   **環境管理**：Monorepo + API Gateway

---

## 架構演進思維
1.  **微服務的本質**：微服務的核心在於「**服務邊界 (Bounded Context)**」與「**獨立部署/運行環境**」，而非是否拆分 Git Repository。
2.  **務實的開發策略**：在開發初期（MVP 階段）為了降低運維成本，採用 **Monorepo (單一程式庫) + 多容器部署 + 獨立資料庫** 的方式。這種方式既守住了微服務的架構邊界，又免去了多專案管理的繁瑣，方便未來隨時進行 `git subtree split` 拆分。

---

## 目錄結構規格設計 (`/docs/project-structure.md`)

整個專案將劃分為前端應用 (`apps`) , 路由網關 (`gateway`) , 後端服務 (`services`) , 共享組件 (`shared`) 以及基礎設施 (`infrastructure`)。

```text
u-badminton-platform/
├── apps/                  # 前端應用程式 (Portal)
│   ├── admin-portal/      # 後台管理系統 (帳號、權限、場館、課程、訂單管理)
│   └── member-portal/     # 前台會員系統 (會員中心、教練、報名、夏令營、訂單查詢)
├── gateway/               # API 網關 (Nginx 路由與反向代理)
│   ├── nginx/
│   ├── config/
│   └── docker/
├── services/              # 獨立的後端微服務 (各自為獨立的 Laravel 專案與 MariaDB)
│   ├── identity-service/  # 身份驗證服務 (Login, JWT, RBAC)
│   ├── user-service/      # 會員與教練個人檔案服務
│   ├── venue-service/     # 場館與球場管理服務
│   ├── course-service/    # 課程、梯次、報名與夏令營服務
│   ├── order-service/     # 訂單、付款狀態與退款服務
│   └── notification-service/ # 通知服務 (第二階段啟用，事件驅動)
├── shared/                # 跨服務共用的合約與定義 (禁止包含 Repository 或 Model)
│   ├── contracts/
│   ├── events/
│   ├── enums/
│   ├── exceptions/
│   └── responses/
├── infrastructure/        # 全域部署環境配置
│   ├── docker/
│   ├── compose/
│   ├── databases/
│   ├── nginx/
│   ├── redis/
│   └── rabbitmq/
├── docker-compose.yml     # 編排網關、微服務、多資料庫與訊息代理
└── README.md
```

---

## 資料庫與通訊設計

### 1. 獨立資料庫架構 (Multi-Database)
為避免資料耦合，每個服務只能存取自身的資料庫：
*   `identity_db`：使用者帳號、角色、權限。
*   `user_db`：會員詳細檔案、教練檔案、員工檔案。
*   `venue_db`：場館、球場、營業時間。
*   `course_db`：課程、課程梯次、報名紀錄、夏令營。
*   `order_db`：訂單、訂單明細、付款、退款。
*   `notification_db`：通知範本、發送日誌。

> [!WARNING]
> **外鍵限制 (Foreign Keys)**：資料庫之間禁止建立物理外鍵約束 (Foreign Key Constraints)。例如 `orders.course_id` 僅保留數值，不建立關聯，跨服務的資料一致性由應用程式層或最終一致性確保。

### 2. 服務間通訊 (Communication)
*   **同步查詢**：透過 `Infrastructure/Clients`（如 `UserClient`、`CourseClient`）封裝 HTTP REST API 進行跨服務調用，嚴禁在業務程式碼中隨處撰寫 `Http::get()`。未來可無痛升級為 gRPC。
*   **非同步寫入/事件驅動**：利用訊息佇列 (**RabbitMQ**) 發布領域事件（例如 `order.paid`），由其他服務監聽並異步處理（如 `course-service` 扣除課程名額、`notification-service` 發送確認信）。

---

## 後端服務內部代碼分層 (DDD / Clean Architecture)
每個在 `services/` 底下的獨立 Laravel 專案均遵循以下分層結構：

```text
app/
├── Domains/             # 核心商業邏輯 (純業務，不依賴外部框架)
│   └── Course/          # 以 Course 領域為例
│       ├── Models/      # 僅定義資料結構 (Data Schema)
│       ├── Repositories/# 僅負責資料存取 (禁止包含商業邏輯)
│       ├── Services/    # Domain Services (處理跨實體的商業邏輯)
│       ├── Actions/     # 單一業務流程 (如 CreateCourseAction, EnrollCourseAction)
│       ├── ValueObjects/# 值物件
│       └── Exceptions/  # 領域異常
├── Application/         # Use Case 層 (負責協調領域邏輯)
│   └── Course/
│       ├── Commands/    # 命令對象
│       ├── Queries/     # 查詢對象
│       └── Handlers/    # 命令/查詢處理器
├── Infrastructure/      # 外部系統與技術細節實作
│   ├── Persistence/     # Repository 實作
│   ├── Clients/         # 跨服務調用客戶端 (如 UserClient, OrderClient)
│   └── Cache/Queue/
└── Http/                # 介面呈現層 (不包含商業邏輯，僅做 Request/Response 轉換)
    ├── Controllers/     # 僅負責調用 Application Handler 或 Domain Action
    ├── Requests/        # 輸入驗證
    └── Resources/       # 輸出格式化
```

---

## 核心開發規範 (8 大開發原則)

為確保未來能夠「無痛拆分微服務」，所有團隊成員必須嚴格遵守以下規則：

| 規則編號 | 規則內容 | 核心目的與考量 |
| :--- | :--- | :--- |
| **Rule 1** | **每個 Service 擁有自己獨立的 DB** | 避免在資料庫層產生物理耦合。 |
| **Rule 2** | **禁止跨 Service 引用 Model** | 例如 `order-service` 絕對不能使用 `use App\Models\Course`，必須透過 API Client 取得資料。 |
| **Rule 3** | **禁止跨 Service 引用 Repository** | 確保各服務的資料存取邏輯完全封裝於自身服務內。 |
| **Rule 4** | **禁止跨 Service 執行 Migration** | 每個服務的資料表結構變更必須由該服務的程式碼庫獨立管理與執行。 |
| **Rule 5** | **所有跨服務操作必須透過 API 或 Event** | 同步查詢走 API，非同步寫入或狀態變更走 Event Bus (RabbitMQ)。 |
| **Rule 6** | **Controller 不得包含任何商業邏輯** | Controller 僅負責 Request 驗證、分發給 Action/Handler，並返回 Response。 |
| **Rule 7** | **Action 為最小業務單元** | 遵循單一職責原則 (Single Responsibility)，將業務流程封裝在獨立的 Action 類別中。 |
| **Rule 8** | **所有 Service 必須具備獨立拆分 Repo 的能力** | 代碼與配置設計 must 做到「隨時剪下、貼上到新 Repo」就能運作的程度。 |

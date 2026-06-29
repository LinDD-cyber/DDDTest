# 實作計畫：DDD 微服務 Monorepo

此執行計畫概述了在單一程式庫 (Monorepo) 中建置基於領域驅動設計 (DDD) 的微服務架構之步驟。本計畫利用 `pnpm workspaces` 進行套件管理、使用 `Turborepo` 進行建置協調，並以 TypeScript 作為主要開發語言。

## 概述
我們將建立一個具備可擴充性、易於維護的 Monorepo 範本，其中包含低耦合的微服務（限界上下文，Bounded Contexts）。這些微服務透過 API 網關進行同步通訊，並透過訊息代理程式 (Message Broker) 進行非同步通訊。每個微服務都遵循乾淨/六角架構 (Clean/Hexagonal Architecture) 原則，以將領域業務邏輯與基礎設施細節進行隔離。

```
                  ┌─────────────────┐
                  │   BFF / 網關    │ (REST / GraphQL)
                  └────────┬────────┘
                           │ (HTTP/gRPC 路由)
         ┌─────────────────┴─────────────────┐
         ▼                                   ▼
┌──────────────────┐               ┌──────────────────┐
│   用戶微服務     │               │   訂單微服務     │
│ (限界上下文)     │               │ (限界上下文)     │
└────────┬─────────┘               └────────┬─────────┘
         │ (領域事件)                        │
         └─────────┬───────────────┬────────┘
                   ▼               ▼
             ┌───────────────────────────┐
             │       訊息代理程式        │ (RabbitMQ / Kafka)
             └───────────────────────────┘
```

---

## 架構決策

*   **Monorepo 工具鏈 (`pnpm` + `Turborepo`)**：
    *   *決策*：使用 `pnpm` workspaces 進行套件連結/解析，並使用 `Turborepo` 進行建置快取與任務管道 (Task Pipelining) 執行。
    *   *考量*：藉由本地與遠端快取來最小化磁碟空間佔用、加速建置時間，並在工作區之間提供乾淨的依賴關係管理。
*   **限界上下文隔離 (Bounded Context Separation)**：
    *   *決策*：每個微服務均位於 `apps/` 或 `services/` 目錄下，代表獨立的 DDD 限界上下文。禁止在資料庫層進行跨上下文呼叫，所有通訊必須透過公開的 API 或事件進行。
*   **內部架構分層 (乾淨架構)**：
    *   *決策*：標準化每個服務內部的 4 個分層：
        1.  `Domain` (領域層)：核心實體 (Entities)、值物件 (Value Objects)、領域邏輯與儲存庫介面 (Repository Interfaces)。不依賴任何外部套件。
        2.  `Application` (應用層)：用例 (Use Cases)、命令 (Commands)、查詢 (Queries) 與領域事件處理器 (Domain Event Handlers)。
        3.  `Infrastructure` (基礎設施層)：資料庫實作、Web 框架設定、外部 API 用戶端、訊息代理程式轉接器。
        4.  `Interface` / `Presentation` (介面/呈現層)：控制器 (Controllers)、CLI 命令、gRPC/HTTP 端點。
*   **共享程式庫 (`packages/` 或 `libs/`)**：
    *   *決策*：將共享的 TS 設定、Linter 規則以及可重用的 DDD 基礎類別放在 `packages/` 目錄下。保持共享的功能性程式碼最少化，以防服務間產生緊密耦合。

---

## 任務列表

### Phase 1: Monorepo 基礎與工作區設定

#### Task 1: 初始化工作區與 Turborepo
*   **描述**：設定基本的 Monorepo 目錄版面，配置 `pnpm-workspace.yaml` 並初始化 `turbo.json`。
*   **驗收標準**：
    *   根目錄 `package.json` 設定正確的 private workspace 屬性。
    *   建立 `apps/` 與 `packages/` 目錄。
    *   定義 `turbo.json`，配置 `build`、`lint`、`test` 與 `dev` 等管道。
*   **驗證步驟**：
    *   在根目錄成功執行 `pnpm install`。
    *   執行 `pnpm turbo run build` 且無錯誤發生（即使目前為空）。
*   **依賴關係**：無
*   **預計修改檔案**：
    *   `package.json` (根目錄)
    *   `pnpm-workspace.yaml`
    *   `turbo.json`
*   **預估工作量**：Small（1-2 個設定檔）

#### Task 2: 建立共享工具套件
*   **描述**：在 `packages/` 下建立可重用的 npm 套件，用於 TypeScript 設定 (`@ddd/tsconfig`)、程式碼檢查 (`@ddd/eslint-config`) 和排版格式化。
*   **驗收標準**：
    *   `packages/tsconfig` 包含適用於程式庫與應用程式目標的基礎 `tsconfig.json` 設定。
    *   `packages/eslint-config` 匯出全域規則與代碼規範。
    *   其他套件引用這些基礎配置，而非重複複製設定。
*   **驗證步驟**：
    *   在服務中建立測試檔案，並確認 `pnpm lint` 是否能根據共享規則標記出違反規範的程式碼。
*   **依賴關係**：Task 1
*   **預計修改檔案**：
    *   `packages/tsconfig/package.json`
    *   `packages/tsconfig/base.json`
    *   `packages/eslint-config/package.json`
    *   `packages/eslint-config/index.js`
*   **預估工作量**：Small（3-4 個設定檔）

#### Task 3: 開發核心 DDD 共享程式庫 (`@ddd/core`)
*   **描述**：建立一個共享程式庫，包含泛型的 DDD 抽象類別（Entity, ValueObject, DomainEvent, AggregateRoot, UniqueIdentifier）。
*   **驗收標準**：
    *   不可變的 `ValueObject` 基礎類別，支援深層等價性檢查。
    *   `Entity` 與 `AggregateRoot` 基礎類別，支援本機事件累積。
    *   定義 `DomainEvent` 介面與內部事件總線介面。
*   **驗證步驟**：
    *   `@ddd/core` 內部的單元測試通過，證明 `ValueObject` 的等價性與 `AggregateRoot` 的事件追蹤功能正常。
    *   指令：`pnpm --filter @ddd/core test`
*   **依賴關係**：Task 2
*   **預計修改檔案**：
    *   `packages/core/package.json`
    *   `packages/core/src/domain/Entity.ts`
    *   `packages/core/src/domain/ValueObject.ts`
    *   `packages/core/src/domain/AggregateRoot.ts`
    *   `packages/core/src/domain/DomainEvent.ts`
*   **預估工作量**：Medium（5-6 個源代碼/測試檔案）

### 檢查點 (Checkpoint)：基礎架構設定
*   [ ] Monorepo 結構可順利編譯。
*   [ ] 共享程式庫能透過 workspaces 正確解析（例如：在其他套件中可成功匯入 `@ddd/core`）。
*   [ ] 執行 `pnpm turbo run test` 可在所有套件中執行測試。

---

### Phase 2: 核心微服務與網關（垂直切片，Vertical Slice）

#### Task 4: 實作用戶微服務 `user-service`
*   **描述**：建置第一個微服務（`user-service`），依循 DDD 乾淨架構實作用戶限界上下文。
*   **驗收標準**：
    *   `Domain` 層包含 `User` 聚合根 (Aggregate Root) 以及 `Email` / `Password` 值物件。
    *   `Application` 層包含 `RegisterUser` 用例。
    *   `Infrastructure` 層包含記憶體內（後續替換為 Prisma/TypeORM）的儲存庫實作。
    *   `Interface` 層暴露 REST/gRPC API。
*   **驗證步驟**：
    *   透過直接 HTTP 測試或 gRPC 測試，確認用戶建立功能正常並返回適當的 Schema。
    *   指令：`pnpm --filter user-service test`
*   **依賴關係**：Task 3
*   **預計修改檔案**：
    *   `apps/user-service/package.json`
    *   `apps/user-service/src/domain/...`
    *   `apps/user-service/src/application/...`
    *   `apps/user-service/src/infrastructure/...`
    *   `apps/user-service/src/interface/...`
*   **預估工作量**：Medium to Large（8-10 個檔案）

#### Task 5: 建置 API 網關 (Routing Layer)
*   **描述**：建立單一入口網關服務，負責將傳入的請求路由至對應的微服務。
*   **驗收標準**：
    *   設定可配置的路由，將請求代理轉發至 `user-service`。
    *   基本的請求驗證與錯誤格式標準化。
    *   驗證或轉發授權標頭 (Auth Headers)。
*   **驗證步驟**：
    *   傳送 HTTP 請求至 `http://localhost:3000/api/users` 可成功路由至 `user-service` 並正確返回結果。
*   **依賴關係**：Task 4
*   **預計修改檔案**：
    *   `apps/api-gateway/package.json`
    *   `apps/api-gateway/src/...`
*   **預估工作量**：Medium（3-5 個檔案）

### 檢查點 (Checkpoint)：垂直整合
*   [ ] API 網關能路由流量至 `user-service`。
*   [ ] 可透過網關端到端執行用戶註冊流程。
*   [ ] 嚴格遵守資料庫交易邊界（僅在微服務上下文內部修改資料）。

---

### Phase 3: 事件驅動基礎設施與最終一致性

#### Task 6: 實作共享訊息套件 (`@ddd/event-bus`)
*   **描述**：建立一個共享包，封裝使用訊息代理程式（例如 RabbitMQ 或 Redis Pub/Sub）進行事件發佈與訂閱的功能。
*   **驗收標準**：
    *   定義 `IEventBus` 介面，包含 `publish` 與 `subscribe` 方法。
    *   實作 RabbitMQ 或 Redis 的具體驅動程式。
    *   加入斷線重連邏輯與基本錯誤處理。
*   **驗證步驟**：
    *   整合測試證明可將事件發佈至測試佇列，且訂閱端能成功消費。
*   **依賴關係**：Task 3
*   **預計修改檔案**：
    *   `packages/event-bus/package.json`
    *   `packages/event-bus/src/...`
*   **預估工作量**：Medium（4-5 個檔案）

#### Task 7: 實作通知微服務 `notification-service`
*   **描述**：建立一個微服務，負責訂閱 `user-service` 發佈的 `UserCreatedEvent`，並觸發模擬發送郵件或通知。
*   **驗收標準**：
    *   訂閱 `UserCreated` 主題 (Topic) 或路由鍵 (Routing Key)。
    *   冪等性處理事件（防止重複發送通知）。
    *   記錄模擬發送通知的成功日誌。
*   **驗證步驟**：
    *   透過 API 網關註冊用戶，確認 `user-service` 發布事件，且 `notification-service` 成功消費並寫入成功日誌。
*   **依賴關係**：Task 5, Task 6
*   **預計修改檔案**：
    *   `apps/notification-service/package.json`
    *   `apps/notification-service/src/...`
*   **預估工作量**：Medium（5-6 個檔案）

### 檢查點 (Checkpoint)：事件驅動驗證
*   [ ] 事件總線成功執行發佈/訂閱循環。
*   [ ] 當 `notification-service` 暫時離線時，系統仍能正常運作（事件佇列能正常積壓與待後續處理）。

---

### Phase 4: DevOps 與本機開發環境優化

#### Task 8: Docker 化與編排設定
*   **描述**：為每個服務新增 Docker 設定檔，並使用 Docker Compose 進行編排，以便於本機一鍵啟動。
*   **驗收標準**：
    *   為 Node.js monorepo 各服務配置多階段 (Multi-stage) 最佳化 `Dockerfile`。
    *   設定 `docker-compose.yml`，定義網關、用戶服務、通知服務、資料庫以及訊息代理程式。
*   **驗證步驟**：
    *   執行 `docker compose up` 可啟動完整系統。
    *   透過單一健康檢查腳本確認所有服務均正常運作。
*   **依賴關係**：Task 7
*   **預計修改檔案**：
    *   `apps/user-service/Dockerfile`
    *   `apps/notification-service/Dockerfile`
    *   `apps/api-gateway/Dockerfile`
    *   `docker-compose.yml`
*   **預估工作量**：Medium（5-6 個設定檔）

---

## 風險與因應對策

| 風險 | 影響程度 | 因應對策 |
| :--- | :--- | :--- |
| **共享程式庫造成高耦合** | 高 | 嚴格審查 `@ddd/core` 與其他套件，確保它們不會匯入屬於特定微服務的領域模型或業務邏輯。保持其純粹的工具屬性。 |
| **Monorepo 建置速度變慢** | 中 | 妥善利用 Turborepo 的依賴關係圖與快取配置。確保建置輸出能基於檔案雜湊值的變化進行快取。 |
| **事件一致性延遲** | 中 | 在事件總線基礎設施中實作重試佇列與死信交換機 (DLX)。在整合測試中實作最終一致性檢查。 |

## 開放性問題
1.  **本機開發與生產環境應預設使用哪種訊息代理程式？**（目前規劃以 RabbitMQ 為預設值，但可隨時替換為 Redis 或 Kafka）。
2.  **在 Monorepo 中應如何處理資料庫遷移 (Database Migrations)？**（應該是各服務啟動時自行執行遷移，還是在 Phase 4 透過全域指令統一管理？）
3.  **內部服務間通訊是否應採用 gRPC 代替 HTTP？**（目前預設使用 REST API 以求簡化，但 gRPC 具有更佳的效能優勢）。

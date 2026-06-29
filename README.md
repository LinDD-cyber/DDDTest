# U-Badminton Platform - Microservices Monorepo Sample

這個專案是一個基於 **Laravel 12 / PHP 8.4** 的微服務 (Microservices) Monorepo 實作範例。展示了服務邊界拆分、獨立資料庫（多 SQLite 設定）以及服務間透過 API Client 進行跨服務通訊。

## 目錄結構
*   `gateway/`：API 網關，使用 Nginx 作為反向代理。
*   `services/user-service/`：用戶微服務（包含會員與教練資訊，獨立 SQLite DB，埠號 `8001`）。
*   `services/order-service/`：訂單微服務（獨立 SQLite DB，埠號 `8002`，會調用 `user-service` 取得用戶資料）。

---

## 啟動方式

### 方法 A：使用 Docker Compose（推薦，需啟動 Docker Desktop）

1.  確認已開啟 Docker Desktop。
2.  在專案根目錄執行以下指令啟動所有服務與網關：
    ```bash
    docker compose up --build -d
    ```
3.  啟動後可透過 API Gateway 統一入口進行測試：
    *   API 網關主頁：`http://localhost`
    *   取得所有用戶（轉發至 `user-service`）：`http://localhost/api/users`
    *   取得單一用戶：`http://localhost/api/users/1`
    *   取得訂單列表（`order-service` 會自動結合跨服務用戶資料）：`http://localhost/api/orders`

---

### 方法 B：本機直接啟動（不需 Docker）

如果您目前未啟動 Docker，可以直接在主機上透過 PHP 與 Composer 啟動：

#### 1. 啟動用戶微服務 (User Service)
開啟終端機 1，執行：
```bash
cd services/user-service
composer install
php artisan serve --port=8001
```
*   測試連結：`http://127.0.0.1:8001/api/users`

#### 2. 啟動訂單微服務 (Order Service)
開啟終端機 2，修改 `services/order-service/.env` 中的 `USER_SERVICE_URL` 為：
```env
USER_SERVICE_URL=http://127.0.0.1:8001
```
然後執行：
```bash
cd services/order-service
composer install
php artisan serve --port=8002
```
*   測試連結：`http://127.0.0.1:8002/api/orders`

---

## 跨服務資料串接展示 (REST API Client Pattern)

當您存取訂單列表 `GET /api/orders` 時：
1.  `order-service` 從自身的資料庫中撈取訂單基本資料。
2.  `order-service` 內部調用 [UserClient.php](file:///D:/project/DDDTest/services/order-service/app/Infrastructure/Clients/UserClient.php) 發送 HTTP 請求至 `user-service` 的 `GET /api/users/{id}`。
3.  將取得的用戶資料整合回訂單 JSON 結構中並返回：
    ```json
    [
      {
        "id": 101,
        "user_id": 1,
        "course_id": 201,
        "amount": 1500,
        "status": "paid",
        "user": {
          "id": 1,
          "name": "陳小明",
          "email": "xiaoming@example.com",
          "role": "member"
        }
      }
    ]
    ```
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

## 啟動步驟 (Docker Compose)

請依序執行以下步驟以逆序啟動專案：

### 1. 啟動基礎設施層 (Infrastructure Layer)
首先，啟動資料庫、快取與訊息佇列等基礎設施，這會自動建立共享的橋接網路 `u-badminton-network`：
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

## 常見問題排除 (Troubleshooting)

### Q1: 資料庫連線失敗 (`Name or service not known`)
如果 `user-service` 或 `order-service` 丟出無法連線至 `mariadb` 的錯誤，可能是 MariaDB 容器在 Docker 網路重啟時斷開了與共享網路 `u-badminton-network` 的連線。

請手動執行以下指令將 MariaDB 重新連回網路並設定別名：
```bash
# 1. 斷開連線 (若有殘留舊連線)
docker network disconnect u-badminton-network dddtest-mariadb-1

# 2. 重新連線並設定別名
docker network connect --alias mariadb u-badminton-network dddtest-mariadb-1
```
# Example Service Template

本資料夾是一個基於領域驅動設計 (Domain-Driven Design, DDD) 的 Laravel 11 微服務範本。你可以直接複製此資料夾來快速建立新的微服務。

---

## 快速複製與建立新服務步驟

假設你要建立一個名為 `payment-service` 的新服務，請依照以下步驟操作：

### 1. 複製資料夾
將此 `example-service` 資料夾複製並重命名為 `payment-service`：
```bash
cp -r services/example-service services/payment-service
```

### 2. 修改 `composer.json`
修改 `services/payment-service/composer.json` 中的名稱與描述：
```json
{
    "name": "u-badminton/payment-service",
    "description": "Payment Service",
    ...
}
```

### 3. 修改 `Dockerfile`
修改 `services/payment-service/Dockerfile` 中的複製路徑：
```dockerfile
# 將這行：
COPY services/example-service/ .

# 修改為：
COPY services/payment-service/ .
```

### 4. 修改 環境變數設定 (`.env.example` 與 `.env`)
修改 `services/payment-service/.env.example` 與 `.env` 中的服務名稱、預設 Port 與資料庫名稱：
```env
APP_NAME=PaymentService
APP_URL=http://localhost:8003  # 請配置一個未被占用的 Port

DB_DATABASE=payment_db         # 該服務對應的獨立資料庫名稱
```

### 5. 註冊至 `docker-compose.yml`
在專案根目錄的 `docker-compose.yml` 中新增 `payment-service` 服務配置：
```yaml
  # =========================
  # Payment Service
  # =========================
  payment-service:
    build:
      context: .
      dockerfile: services/payment-service/Dockerfile
    volumes:
      - ./services/payment-service:/app
      - ./shared:/shared
      - payment-vendor:/app/vendor
    environment:
      APP_ENV: local
      APP_DEBUG: "true"

      DB_CONNECTION: mysql
      DB_HOST: mariadb
      DB_PORT: 3306
      DB_DATABASE: payment_db
      DB_USERNAME: root
      DB_PASSWORD: root@0000

      REDIS_HOST: redis
      REDIS_PORT: 6379

      RABBITMQ_HOST: rabbitmq
      RABBITMQ_PORT: 5672
    restart: unless-stopped
    networks:
      - dddtest-network
```
並在最下方的 `volumes` 區塊加上：
```yaml
volumes:
  ...
  payment-vendor:
```

### 6. 初始化 MariaDB 資料庫 (Database-per-Service)
修改 `infrastructure/mariadb/init-db.sql`，在初始化腳本中新增建立新資料庫的 SQL：
```sql
CREATE DATABASE IF NOT EXISTS `payment_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. 配置 API Gateway 路由分流
修改 `gateway/default.conf`，為新服務配置轉發規則。
* **如果該路由需要透過 API Gateway 進行 Token 驗證**，可參考 `orders` 的寫法（使用 `auth_request`）：
  ```nginx
  location /api/payments {
      auth_request /internal-auth-verify;
      error_page 401 = @error401;

      auth_request_set $auth_user_id $upstream_http_x_user_id;
      proxy_set_header X-User-Id $auth_user_id;

      proxy_pass http://payment-service:8000;
      proxy_set_header Host $host;
      proxy_set_header Accept "application/json";
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
  }
  ```
* **如果是公開路由**，可參考 `/api/auth` 寫法，直接進行 `proxy_pass`。

### 8. 重新啟動並編譯容器
在專案根目錄下重新啟動並建置容器：
```bash
# 啟動基礎設施 (包含資料庫 initialization 建立 payment_db)
docker compose -f docker-compose.infra.yml up -d

# 啟動並建置服務
docker compose up -d --build
```

---

## 內部 DDD 架構開發規範

新服務開發時請遵循以下 DDD 架構設計：
* **`app/Domains/`**: 放各領域 (Domains)，內部區分為 `Models` 與 `Services`。
* **`app/Http/`**: 放對外的介面與應用層（如 `Controllers`、`Requests`、`Middleware` 等）。
* **`app/Infrastructure/`**: 放外部服務客戶端 (如呼叫其他微服務的 API Client) 或與基礎設施有關的實作。
* **共享程式庫使用**：
  * 當執行 `composer install` 後，Composer 會透過 Path Repository 以 Symlink 軟連結自動掛載專案根目錄下的 `shared/common` 通用包。
  * 你可以直接使用 `Shared\Support\ApiResponder`、`Shared\Infrastructure\Clients\...` 等共享套件中的類別。

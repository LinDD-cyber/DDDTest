# Docker & 專案架構設計

本專案採用**微服務自治性 (Microservice Autonomy)** 與 **Database-per-Service** 架構設計。以下為推薦且與目前專案一致的 Docker 與專案目錄結構設計：

```text
u-badminton-platform (專案根目錄)
│
├── gateway/                             # API Gateway (反向代理與路由分流)
│   ├── Dockerfile                       # Gateway 建置定義
│   └── default.conf                     # Nginx 路由轉發設定
│
├── infrastructure/                      # 共享基礎設施 (僅供本地開發或環境設定參考)
│   ├── mariadb/                         # 資料庫設定
│   │   ├── Dockerfile                   # MariaDB 建置定義
│   │   ├── my.cnf                       # MariaDB 設定檔
│   │   └── init-db.sql                  # 資料庫與 Schema 初始化腳本
│   │
│   ├── base-images/
│   │   ├── php/
│   │   │   └── Dockerfile
│   │   └── nginx/
│   │       └── Dockerfile
│   │
│   ├── mariadb/
│   ├── redis/                           # 快取設定 (預留)
#│   ├── rabbitmq/                       # 事件驅動 + 非同步處理（Event Bus）
#│   ├── monitoring/
#│   └── scripts/
│
├── services/                            # 微服務主目錄 (各服務高內聚、低耦合)
│   ├── user-service/                    # 使用者服務 (Laravel)
│   │   ├── Dockerfile                   # 該服務獨立的建置定義 (Production/Dev stage)
│   │   ├── docker-compose.yml
│   │   ├── app/                         # 業務邏輯
│   │   ├── config/
│   │   └── ...
│   │
│   └── order-service/                   # 訂單服務 (Laravel)
│       ├── Dockerfile                   # 該服務獨立的建置定義 (Production/Dev stage)
│       ├── docker-compose.yml
│       ├── app/                         # 業務邏輯
│       ├── config/
│       └── ...
│
├── shared/                              # 共享模組或套件 (如 DTO, 公用工具等)
│
├── .env.example                         # 環境變數範本
├── README.md                            # 專案說明文件
└── docker-compose.yml                   # 主入口
```

## 設計優勢與核心原則

1. **微服務自治性 (Autonomy)**
   - 每個微服務在 `services/` 下擁有專屬的 `Dockerfile`。若 `user-service` 需要特定 PHP 擴充套件，可直接修改其內部的 Dockerfile，完全不會干擾到 `order-service`。
   - CI/CD 流程中可單獨針對發生變動的服務進行 Image 建置與部署，大幅提升構建效率。

2. **本地開發一致性 (Developer Experience)**
   - 避免使用多個碎裂的 `.yml` 檔，改由根目錄的 `docker-compose.yml` 統一管理與啟動所有服務，使開發者只需執行 `docker compose up -d` 即可快速建立完整的開發環境。

3. **統一閘道管理 (API Gateway)**
   - 由前端的 `gateway` 服務統一作為入口，避免各服務需要重複配置 Nginx Sidecar，進而簡化內部容器的通訊結構。

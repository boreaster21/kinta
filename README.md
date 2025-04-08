# coachtech 勤怠アプリ

このアプリケーションは、従業員の勤怠打刻、休憩管理、修正申請を行うための勤怠管理システムです。

## 環境構築 (Docker)

### 必要なもの

-   Docker
-   Docker Compose

### 手順

1.  **リポジトリのクローン:**

    ```bash
    git clone [リポジトリのURL]
    cd [クローンしたディレクトリ名]
    ```

2.  **環境変数の設定:**
    `.env.example` ファイルをコピーして `.env` ファイルを作成し、必要に応じてデータベース接続情報などを編集します。

    ```bash
    cp .env.example .env
    ```

3.  **Docker コンテナのビルドと起動:**

    ```bash
    docker-compose up -d --build
    ```

    -   _注意:_ MySQL コンテナは、お使いの OS や環境によっては正常に起動しない場合があります。その場合は、`compose.yaml` ファイル内の MySQL サービス定義（ポートやボリュームなど）を適宜調整してください。

4.  **PHP コンテナへのアクセス:**

    ```bash
    docker-compose exec php bash
    ```

5.  **(PHP コンテナ内) Composer パッケージのインストール:**

    ```bash
    composer install
    ```

6.  **(PHP コンテナ内) アプリケーションキーの生成:**

    ```bash
    php artisan key:generate
    ```

7.  **(PHP コンテナ内) データベースマイグレーション:**

    ```bash
    php artisan migrate
    ```

8.  **(PHP コンテナ内) データベースシーディング (テストデータの投入):**

    ```bash
    php artisan db:seed
    ```

9.  **コンテナからの退出:**
    ```bash
    exit
    ```

これで環境構築は完了です。

## 使用技術

-   **PHP:** 11 (※ PHP のバージョンは `docker-compose.yaml` または `Dockerfile` をご確認ください。)
-   **フレームワーク:** Laravel 11.x
-   **データベース:** MySQL (バージョンは `compose.yaml` をご確認ください)
-   **Web サーバー:** Nginx ( `compose.yaml` をご確認ください)
-   **コンテナ仮想化:** Docker, Docker Compose

## ER 図

```mermaid
erDiagram
    USERS ||--o{ ATTENDANCES : "has"
    USERS ||--o{ STAMP_CORRECTION_REQUESTS : "requests"
    USERS |o--o{ STAMP_CORRECTION_REQUESTS : "approves/rejects"
    USERS ||--o{ ATTENDANCE_MODIFICATION_HISTORY : "modifies"
    ROLES ||--o{ USERS : "has"
    ATTENDANCES ||--o{ BREAK_TIMES : "has"
    ATTENDANCES ||--o{ STAMP_CORRECTION_REQUESTS : "targets"
    ATTENDANCES ||--o{ ATTENDANCE_MODIFICATION_HISTORY : "history for"

    USERS {
        int id PK
        string name
        string email UK
        int role_id FK
        timestamp created_at
        timestamp updated_at }

    ROLES {
        int id PK
        string name UK
        timestamp created_at
        timestamp updated_at }

    ATTENDANCES {
        int id PK
        int user_id FK
        date date
        timestamp clock_in NULL
        timestamp clock_out NULL
        string total_break_time
        string total_work_time
        text reason NULL
        timestamp created_at
        timestamp updated_at }

    BREAK_TIMES {
        int id PK
        int attendance_id FK
        timestamp start_time
        timestamp end_time NULL
        int duration
        timestamp created_at
        timestamp updated_at }

    STAMP_CORRECTION_REQUESTS {
        int id PK
        int user_id FK "applicant"
        int attendance_id FK
        date date
        timestamp clock_in
        timestamp clock_out
        json break_start
        json break_end
        text reason NULL
        enum status
        timestamp approved_at NULL
        timestamp rejected_at NULL
        int approved_by FK NULL "approver"
        int rejected_by FK NULL "rejector"
        date original_date NULL
        timestamp original_clock_in NULL
        timestamp original_clock_out NULL
        json original_break_start NULL
        json original_break_end NULL
        text original_reason NULL
        timestamp created_at
        timestamp updated_at }

    ATTENDANCE_MODIFICATION_HISTORY {
        int id PK
        int attendance_id FK
        int modified_by FK
        enum modification_type
        timestamp clock_in NULL
        timestamp clock_out NULL
        string total_break_time NULL
        string total_work_time NULL
        text reason NULL
        timestamp created_at
        timestamp updated_at }
```

## URL

-   **開発環境:** [http://localhost/](http://localhost/)
-   **phpMyAdmin:** [http://localhost:8080/](http://localhost:8080/) ( `compose.yaml` でポートが変更されている場合は調整してください)

## ログイン情報 (シーディング後)

-   **管理者ユーザー:**
    -   メールアドレス: `admin@example.com`
    -   パスワード: `adminpass`
-   **一般ユーザー:**
    -   `UserSeeder` で作成されたユーザー情報をご確認ください (メールアドレス/パスワード: `password`)。

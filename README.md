# coachtech 勤怠アプリ

このアプリケーションは、従業員の勤怠打刻、休憩管理、修正申請を行うための勤怠管理システムです。

## 環境構築 (Docker)

### 必要なもの

- Docker
- Docker Compose
- Node.js (v18 以上推奨)
- npm (または yarn)

- **Node.js と npm:**

  - プロジェクトで推奨される Node.js バージョン (例: v18) を利用するために、**Node Version Manager (`nvm`)** を使用してインストールすることを推奨します。

  - **nvm のインストール (未インストールの場合):**

    ```bash
    # nvmのインストールスクリプトを実行 (公式の最新コマンドを確認してください)
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

    # ターミナルを再起動するか、以下のコマンドでnvmを読み込む
    export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
    [ -s "$NVM_DIR/nvm.sh" ] && \\. "$NVM_DIR/nvm.sh"
    ```

  - **nvm を使用した Node.js (v18) のインストール:**
    ```bash
    nvm install 18
    nvm use 18
    # npmも同時にインストールされます
    ```

### 手順

1.  **(ホストマシン) リポジトリのクローン:**

    ```bash
    git clone [リポジトリのURL]
    cd [クローンしたディレクトリ名]
    ```

2.  **(ホストマシン) 環境変数の設定:**
    プロジェクトルートで `.env.example` ファイルをコピーして `.env` ファイルを作成します。必要に応じて内容を編集してください。（特にメール設定など）

    ```bash
    cp src/.env.example src/.env
    # 注意: .env.example は src ディレクトリ内にあります
    ```

3.  **(ホストマシン) Node.js 依存関係のインストール:**
    `package.json` が存在する **`src` ディレクトリ内** で以下を実行します。

    ```bash
    cd src
    npm install
    # または yarn install
    cd ..
    ```

4.  **(ホストマシン) Docker コンテナのビルドと起動:**
    プロジェクトルートで以下を実行します。

    ```bash
    docker-compose up -d --build
    ```

    MySQL データは Docker 名前付きボリューム (`mysql_data`) に永続化されます。初回起動時など、コンテナの起動に時間がかかる場合があります。

5.  **(ホストマシン) PHP コンテナへのアクセス:**
    コンテナが起動したら、以下のコマンドで PHP コンテナのシェルに入ります。

    ```bash
    docker-compose exec php bash
    ```

6.  **(PHP コンテナ内) Composer パッケージのインストール:**

    ```bash
    composer install
    ```

7.  **(PHP コンテナ内) アプリケーションキーの生成:**

    ```bash
    php artisan key:generate
    ```

8.  **(PHP コンテナ内) データベースマイグレーション:**
    データベースのテーブルを作成します。

    ```bash
    php artisan migrate
    ```

9.  **(PHP コンテナ内) データベースシーディング (テストデータの投入):**
    必要に応じて、テスト用の初期データを投入します。

    ```bash
    php artisan db:seed
    ```

10. **(PHP コンテナ内) コンテナからの退出:**

    ```bash
    exit
    ```

11. **(ホストマシン) Vite 開発サーバーの起動:**
    開発時にフロントエンドの変更をリアルタイムに反映させるには、**`src` ディレクトリ内** で Vite 開発サーバーを起動します。（通常、別のターミナルを開いて実行し続けます）

    ```bash
    cd src
    npm run dev
    # または yarn dev
    # 終了する場合は Ctrl+C
    ```

    Vite サーバー起動後、ブラウザでアプリケーション ([http://localhost/](http://localhost/)) にアクセスしてください。

    **(本番環境やビルドする場合)**
    本番環境向けに最適化された静的なアセットファイルを生成する場合は、代わりに **`src` ディレクトリ内** で以下のコマンドを実行します。

    ```bash
    cd src
    npm run build
    # または yarn build
    ```

これで環境構築は完了です。

## 使用技術

- **PHP:** php:8.2 (Dockerfile を参照)
- **フレームワーク:** Laravel 11.x
- **データベース:** mysql:8.0.39
- **Web サーバー:** nginx:1.27.2
- **フロントエンド:** Vite, Node.js
- **コンテナ仮想化:** Docker, Docker Compose

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
        timestamp updated_at
    }

    ROLES {
        int id PK
        string name UK
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCES {
        int id PK
        int user_id FK
        date date
        timestamp clock_in
        timestamp clock_out
        string total_break_time
        string total_work_time
        text reason
        timestamp created_at
        timestamp updated_at
    }

    BREAK_TIMES {
        int id PK
        int attendance_id FK
        timestamp start_time
        timestamp end_time
        int duration
        timestamp created_at
        timestamp updated_at
    }

    STAMP_CORRECTION_REQUESTS {
        int id PK
        int user_id FK
        int attendance_id FK
        date date
        timestamp clock_in
        timestamp clock_out
        json break_start
        json break_end
        text reason
        enum status
        timestamp approved_at
        timestamp rejected_at
        int approved_by FK
        int rejected_by FK
        date original_date
        timestamp original_clock_in
        timestamp original_clock_out
        json original_break_start
        json original_break_end
        text original_reason
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCE_MODIFICATION_HISTORY {
        int id PK
        int attendance_id FK
        int modified_by FK
        enum modification_type
        timestamp clock_in
        timestamp clock_out
        string total_break_time
        string total_work_time
        text reason
        timestamp created_at
        timestamp updated_at
    }
```

## URL

- **開発環境:** [http://localhost/](http://localhost/)
- **phpMyAdmin:** [http://localhost:8080/](http://localhost:8080/)

## ログイン情報 (シーディング後)

- **管理者ユーザー:**

  - メールアドレス: `admin@example.com`
  - パスワード: `adminpass`

- **一般ユーザー:**
  - メールアドレス: `test@example.com`
  - パスワード: `testpass`

## テスト

このプロジェクトでは、アプリケーションの品質を保証するために PHPUnit を使用したテストスイートが含まれています。

### テストの実行方法

1.  **(ホストマシン) PHP コンテナ内に入る:**

    ```bash
    docker-compose exec php bash
    ```

2.  **(PHP コンテナ内) テストを実行する:**
    プロジェクトのルート (`/var/www`) で実行します。

    ```bash
    php artisan test
    ```

    特定のテストファイルのみを実行したい場合は、ファイルパスを指定します。

    ```bash
    php artisan test tests/Feature/Auth/AuthenticationTest.php
    ```

    特定のメソッドのみを実行したい場合は、`--filter` オプションを使用します。

    ```bash
    php artisan test --filter=login_fails_when_email_is_missing
    ```

### テストの内容

`tests/` ディレクトリには、以下の種類のテストが含まれています。

- **Feature テスト (`tests/Feature`):** アプリケーションの主要な機能（認証、勤怠打刻、修正申請、管理者機能など）に関するエンドツーエンドに近いテスト。

  - `Auth/`: ログイン、登録、管理者認証などの認証関連テスト。
  - `Attendance/`: 一般ユーザー向けの勤怠打刻、休憩、一覧表示、詳細表示、修正申請に関するテスト。
  - `Admin/`: 管理者向けのスタッフ一覧、勤怠一覧、勤怠詳細/更新、修正申請の承認/却下に関するテスト。

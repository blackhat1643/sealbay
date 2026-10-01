<?php
/**
 * Database access (PDO). MySQL in production; SQLite supported for quick local setups.
 * Every query in the project goes through prepared statements.
 */
defined('ST_APP') || exit;

function db_driver(): string
{
    return config('db.driver') === 'sqlite' ? 'sqlite' : 'mysql';
}

function db_configured(): bool
{
    if (db_driver() === 'sqlite') {
        return extension_loaded('pdo_sqlite');
    }
    return (string) config('db.name', '') !== '' && (string) config('db.user', '') !== '';
}

/** Shared PDO connection, or null when the database is not configured / unreachable. */
function db(): ?PDO
{
    static $pdo = null, $failed = false;
    if ($pdo !== null || $failed) {
        return $pdo;
    }
    if (!db_configured()) {
        $failed = true;
        return null;
    }
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        if (db_driver() === 'sqlite') {
            $path = (string) (config('db.sqlite_path') ?: ST_STORAGE . '/database.sqlite');
            $pdo  = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                config('db.host', 'localhost'),
                (int) config('db.port', 3306),
                config('db.name'),
                config('db.charset', 'utf8mb4')
            );
            if (config('db.ssl')) {
                // Cloud databases require TLS. Use the system CA bundle to verify the server.
                $ca = '';
                foreach ([(string) (openssl_get_cert_locations()['default_cert_file'] ?? ''), '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $file) {
                    if ($file !== '' && is_file($file)) {
                        $ca = $file;
                        break;
                    }
                }
                // PHP 8.5 moved the driver constants to Pdo\Mysql; older versions only have the PDO:: ones.
                $sslCa = defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : constant('PDO::MYSQL_ATTR_SSL_CA');
                if ($ca !== '') {
                    $options[$sslCa] = $ca;
                } else {
                    app_log('db(): TLS requested but no CA bundle was found on this server.');
                }
            }
            $pdo = new PDO($dsn, (string) config('db.user'), (string) config('db.pass'), $options);
        }
    } catch (PDOException $ex) {
        $failed = true;
        $pdo    = null;
        app_log('Database connection failed: ' . $ex->getMessage());
    }
    return $pdo;
}

/** True once the installer has created the tables. */
function db_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    // On normal hosting the installer leaves a lock file. Without a persistent disk
    // there is nowhere to keep one, so the database itself is the marker.
    if ((!storage_in_db() && !is_file(ST_STORAGE . '/installed.lock')) || !db()) {
        return $ready = false;
    }
    try {
        db()->query('SELECT 1 FROM settings LIMIT 1');
        return $ready = true;
    } catch (PDOException $ex) {
        return $ready = false;
    }
}

/** Run a prepared statement and return it. */
function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = []): mixed
{
    return db_run($sql, $params)->fetchColumn();
}

function db_now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Table definitions for the current driver. database/schema.sql is the MySQL
 * version of exactly these statements (for importing through phpMyAdmin).
 * Money is stored in cents (AUD, GST inclusive).
 */
function schema_statements(?string $driver = null): array
{
    $mysql = ($driver ?? db_driver()) === 'mysql';
    $pk    = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $int   = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $bool  = $mysql ? 'TINYINT(1)' : 'INTEGER';
    $long  = $mysql ? 'MEDIUMTEXT' : 'TEXT';
    $blob  = $mysql ? 'MEDIUMBLOB' : 'BLOB';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    return [
        "CREATE TABLE IF NOT EXISTS admins (
            id $pk,
            username VARCHAR(60) NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            last_login_at DATETIME NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS login_attempts (
            id $pk,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(60) NOT NULL,
            attempted_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS categories (
            id $pk,
            slug VARCHAR(120) NOT NULL UNIQUE,
            name VARCHAR(160) NOT NULL,
            headline VARCHAR(200) NULL,
            summary TEXT NULL,
            description TEXT NULL,
            illustration VARCHAR(40) NULL,
            image VARCHAR(190) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active $bool NOT NULL DEFAULT 1,
            updated_at DATETIME NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS products (
            id $pk,
            category_id $int NOT NULL,
            slug VARCHAR(160) NOT NULL UNIQUE,
            sku VARCHAR(60) NOT NULL UNIQUE,
            name VARCHAR(200) NOT NULL,
            seal_type VARCHAR(20) NOT NULL DEFAULT 'rotary',
            style VARCHAR(120) NULL,
            material VARCHAR(60) NULL,
            inner_diameter DECIMAL(7,2) NULL,
            outer_diameter DECIMAL(7,2) NULL,
            width DECIMAL(7,2) NULL,
            temp_range VARCHAR(80) NULL,
            price_cents INT NOT NULL DEFAULT 0,
            stock_qty INT NOT NULL DEFAULT 0,
            allow_backorder $bool NOT NULL DEFAULT 1,
            summary TEXT NULL,
            fitment TEXT NULL,
            kit_contents TEXT NULL,
            illustration VARCHAR(40) NULL,
            image VARCHAR(190) NULL,
            is_featured $bool NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active $bool NOT NULL DEFAULT 1,
            updated_at DATETIME NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )$tail",

        "CREATE TABLE IF NOT EXISTS orders (
            id $pk,
            order_number VARCHAR(20) NOT NULL UNIQUE,
            access_token VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'awaiting_payment',
            payment_method VARCHAR(20) NOT NULL,
            payment_ref VARCHAR(190) NULL,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            company VARCHAR(160) NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            address_line1 VARCHAR(160) NOT NULL,
            address_line2 VARCHAR(160) NULL,
            suburb VARCHAR(100) NOT NULL,
            state VARCHAR(3) NOT NULL,
            postcode VARCHAR(4) NOT NULL,
            shipping_method VARCHAR(20) NOT NULL,
            shipping_cents INT NOT NULL DEFAULT 0,
            subtotal_cents INT NOT NULL DEFAULT 0,
            total_cents INT NOT NULL DEFAULT 0,
            gst_cents INT NOT NULL DEFAULT 0,
            customer_notes TEXT NULL,
            admin_notes TEXT NULL,
            tracking_number VARCHAR(80) NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            shipped_at DATETIME NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS order_items (
            id $pk,
            order_id $int NOT NULL,
            product_id $int NULL,
            sku VARCHAR(60) NOT NULL,
            name VARCHAR(200) NOT NULL,
            unit_price_cents INT NOT NULL,
            quantity INT NOT NULL,
            line_total_cents INT NOT NULL,
            backorder_qty INT NOT NULL DEFAULT 0,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )$tail",

        "CREATE TABLE IF NOT EXISTS articles (
            id $pk,
            slug VARCHAR(160) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            topic VARCHAR(80) NULL,
            excerpt TEXT NULL,
            body $long NULL,
            faqs TEXT NULL,
            reading_minutes INT NOT NULL DEFAULT 5,
            meta_title VARCHAR(200) NULL,
            meta_description VARCHAR(320) NULL,
            is_published $bool NOT NULL DEFAULT 1,
            published_at DATETIME NULL,
            updated_at DATETIME NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS enquiries (
            id $pk,
            reference VARCHAR(20) NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'quote',
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(40) NULL,
            postcode VARCHAR(10) NULL,
            seal_type VARCHAR(120) NULL,
            inner_diameter VARCHAR(60) NULL,
            outer_diameter VARCHAR(60) NULL,
            width VARCHAR(60) NULL,
            quantity VARCHAR(60) NULL,
            machine VARCHAR(250) NULL,
            message TEXT NULL,
            attachment_path VARCHAR(190) NULL,
            attachment_name VARCHAR(190) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            admin_notes TEXT NULL,
            created_at DATETIME NOT NULL
        )$tail",

        // The next three tables are only used when app.storage = 'db' (hosts without a persistent disk).
        "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data $long NOT NULL,
            expires_at INT NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS uploads (
            name VARCHAR(80) NOT NULL PRIMARY KEY,
            kind VARCHAR(20) NOT NULL,
            mime VARCHAR(80) NOT NULL,
            width INT NOT NULL DEFAULT 0,
            height INT NOT NULL DEFAULT 0,
            data $blob NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS rate_hits (
            id $pk,
            bucket VARCHAR(64) NOT NULL,
            hit_at INT NOT NULL
        )$tail",

        'CREATE INDEX idx_sessions_expiry ON sessions (expires_at)',
        'CREATE INDEX idx_rate_hits ON rate_hits (bucket, hit_at)',
        'CREATE INDEX idx_enquiries_status ON enquiries (status, created_at)',
        'CREATE INDEX idx_login_attempts ON login_attempts (ip_address, attempted_at)',
        'CREATE INDEX idx_products_category ON products (category_id, sort_order)',
        'CREATE INDEX idx_products_size ON products (inner_diameter, outer_diameter, width)',
        'CREATE INDEX idx_orders_status ON orders (status, created_at)',
        'CREATE INDEX idx_order_items_order ON order_items (order_id)',
    ];
}

<?php
// ============================================================
//  db.php  —  Database configuration & connection
//  Edit DB_HOST, DB_NAME, DB_USER, DB_PASS to match your
//  InfinityFree MySQL credentials from the control panel.
// ============================================================

define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_41613642_cyberpulse_db');   // e.g. if12345678_cyberpulse
define('DB_USER', 'if0_41613642');   // e.g. if12345678_ravindu
define('DB_PASS', 'IRvUdij4ulxqp');
define('DB_CHARSET', 'utf8mb4');

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

// ── Run once to create tables ─────────────────────────────────────────
function init_db(): void {
    $pdo = get_pdo();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            username    VARCHAR(64) NOT NULL UNIQUE,
            password    VARCHAR(255) NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS posts (
            id          VARCHAR(64) PRIMARY KEY,
            type        ENUM('daily','weekly') NOT NULL,
            title       VARCHAR(255) DEFAULT '',
            content     TEXT NOT NULL,
            post_date   DATE NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS map_events (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(255) NOT NULL,
            description TEXT DEFAULT '',
            lat         DECIMAL(10,7) NOT NULL,
            lng         DECIMAL(10,7) NOT NULL,
            event_date  DATE NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS login_attempts (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            ip          VARCHAR(64) NOT NULL,
            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip, attempted_at)
        ) ENGINE=InnoDB;
    ");

    // Seed the user if not already present
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['ravindu']);
    if (!$stmt->fetch()) {
        $hash = password_hash('X892j-09', PASSWORD_BCRYPT);
        $ins  = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $ins->execute(['ravindu', $hash]);
    }
}

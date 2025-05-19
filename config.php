<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SQLite database configuration
try {
    $dbPath = __DIR__ . '/database.sqlite';
    $conn = new PDO("sqlite:$dbPath");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            balance DECIMAL(10,2) DEFAULT 0.00,
            is_admin INTEGER DEFAULT 0,
            is_blocked INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            type TEXT CHECK(type IN ('regular', 'software')) NOT NULL,
            stock INTEGER DEFAULT 0,
            stock_messages TEXT,
            image_url TEXT,
            download_link TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Add new columns to products table if they don't exist
        PRAGMA foreign_keys=off;
        BEGIN TRANSACTION;
        ALTER TABLE products RENAME TO _products_old;
        CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            type TEXT CHECK(type IN ('regular', 'software')) NOT NULL,
            stock INTEGER DEFAULT 0,
            stock_messages TEXT,
            image_url TEXT,
            download_link TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        INSERT INTO products (id, name, description, price, type, stock, download_link, created_at)
        SELECT id, name, description, price, type, stock, download_link, created_at FROM _products_old;
        DROP TABLE _products_old;
        COMMIT;
        PRAGMA foreign_keys=on;

        CREATE TABLE IF NOT EXISTS software (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            version TEXT NOT NULL,
            file_path TEXT NOT NULL,
            download_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            product_id INTEGER,
            code_or_key TEXT,
            delivery_message TEXT,
            status TEXT CHECK(status IN ('pending', 'completed', 'failed')) DEFAULT 'completed',
            purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );

        -- Add delivery_message column if it doesn't exist
        PRAGMA foreign_keys=off;
        BEGIN TRANSACTION;
        ALTER TABLE purchases RENAME TO _purchases_old;
        CREATE TABLE purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            product_id INTEGER,
            code_or_key TEXT,
            delivery_message TEXT,
            status TEXT CHECK(status IN ('pending', 'completed', 'failed')) DEFAULT 'completed',
            purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );
        INSERT INTO purchases (id, user_id, product_id, code_or_key, status, purchase_date)
        SELECT id, user_id, product_id, code_or_key, status, purchase_date FROM _purchases_old;
        DROP TABLE _purchases_old;
        COMMIT;
        PRAGMA foreign_keys=on;

        CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS balance_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            amount DECIMAL(10,2) NOT NULL,
            type TEXT CHECK(type IN ('deposit', 'purchase', 'refund')) NOT NULL,
            status TEXT CHECK(status IN ('pending', 'completed', 'failed')) DEFAULT 'pending',
            transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");

    // Insert default admin user if not exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'root'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, balance) VALUES (?, ?, ?, 1, 0)");
        $stmt->execute(['root', 'root@example.com', password_hash('123', PASSWORD_DEFAULT)]);
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

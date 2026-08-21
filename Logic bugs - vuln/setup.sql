-- ============================================================

-- Lab: Excessive Trust in Client-Side Controls

-- setup.sql - database schema + seed data

-- ============================================================

CREATE DATABASE IF NOT EXISTS lab2_client_control
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE lab2_client_control;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    store_credit DECIMAL(10,2) NOT NULL DEFAULT 0.00
);

CREATE TABLE products (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    description  TEXT,
    price        DECIMAL(10,2) NOT NULL,
    image_emoji  VARCHAR(10) DEFAULT '🧥'
);

CREATE TABLE cart_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    total      DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO users (username, password, store_credit) VALUES
    ('wiener', 'peter', 50.00);

INSERT INTO products (name, description, price, image_emoji) VALUES
    ('Lightweight l33t leather jacket',
     'A genuine, lightweight leather jacket for the elite hacker wardrobe. Real price: $1337.00. Store credit alone will never cover this... or will it?',
     1337.00,
     '🧥');

INSERT INTO products (name, description, price, image_emoji) VALUES
    ('Giant novelty mug',
     'Holds an entire pot of coffee. Perfect for late-night pentesting.',
     12.00,
     '☕'),

    ('"I hack therefore I am" T-shirt',
     'Soft cotton tee for the security-minded.',
     19.99,
     '👕');
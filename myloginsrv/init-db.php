<?php
$db = new PDO('sqlite:/var/www/html/users.db');
$db->exec("CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT UNIQUE NOT NULL,
 password TEXT NOT NULL,
 role TEXT NOT NULL,
 active INTEGER DEFAULT 1,
 redirect_urls TEXT DEFAULT '[]',
 reset_token TEXT DEFAULT NULL,
 reset_expires INTEGER DEFAULT NULL
)");
?>
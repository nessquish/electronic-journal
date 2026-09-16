<?php
require_once 'config.php';

function getPDO() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;'); // включаем внешние ключи
        return $pdo;
    } catch (PDOException $e) {
        die('Ошибка подключения: ' . $e->getMessage());
    }
}
?>
<?php

/**
 * Единственное место, где создаётся подключение к базе.
 */
class Database
{
    // Подключение скрыто от других классов
    private PDO $pdo;

    public function __construct(string $path = __DIR__ . '/../journal.db')
    {
        // Открываем файл базы
        $this->pdo = new PDO('sqlite:' . $path);
        // Ошибки превращаем в исключения
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Строки получаем как массивы
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Включаем проверку связей
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * Выполняет запрос с параметрами через подготовленное выражение.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        // Готовим запрос
        $stmt = $this->pdo->prepare($sql);

        // Подставляем значения
        foreach ($params as $key => $value) {
            // Позиционные параметры в PDO нумеруются с единицы
            $name = is_int($key) ? $key + 1 : $key;
            // Числа передаём как числа
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        // Выполняем запрос
        $stmt->execute();
        return $stmt;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Нужен страницам, которые ещё не переведены на классы.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}

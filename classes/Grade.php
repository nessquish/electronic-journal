<?php

/**
 * Выборка оценок для страницы журнала.
 */
class Grade
{
    // Общая часть запросов
    private const FROM = 'FROM grades g
        JOIN students s ON s.id = g.student_id
        JOIN subjects sub ON sub.id = g.subject_id';

    // Подключение к базе
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Количество записей с учётом условий.
     */
    public function count(string $where, array $params): int
    {
        return (int) $this->db->query('SELECT COUNT(*) ' . self::FROM . " $where", $params)->fetchColumn();
    }

    /**
     * Записи одной страницы. $order формируется только из белого списка.
     */
    public function getPage(string $where, array $params, string $order, int $limit, int $offset): array
    {
        // Запрос одной страницы
        $sql = 'SELECT g.id, s.full_name, s.group_name, sub.title, g.grade, g.date '
            . self::FROM . " $where $order LIMIT :limit OFFSET :offset";

        return $this->db->query($sql, $params + ['limit' => $limit, 'offset' => $offset])->fetchAll();
    }
}

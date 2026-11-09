<?php

require_once __DIR__ . '/Report.php';

/**
 * Средний балл групп по предметам.
 */
class GroupAverageReport extends Report
{
    protected function getTitle(): string
    {
        return 'Средний балл группы по предмету';
    }

    protected function getColumns(): array
    {
        return [
            'group_name' => 'Группа',
            'subject' => 'Предмет',
            'avg_grade' => 'Средний балл',
            'total' => 'Оценок',
        ];
    }

    protected function getQuery(): array
    {
        $sql = 'SELECT s.group_name, sub.title AS subject, AVG(g.grade) AS avg_grade, COUNT(*) AS total
            FROM grades g
            JOIN students s ON s.id = g.student_id
            JOIN subjects sub ON sub.id = g.subject_id
            GROUP BY s.group_name, sub.id
            ORDER BY s.group_name, sub.title';

        return [$sql, []];
    }
}

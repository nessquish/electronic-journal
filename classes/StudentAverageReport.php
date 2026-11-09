<?php

require_once __DIR__ . '/Report.php';

/**
 * Средний балл студента по предметам.
 */
class StudentAverageReport extends Report
{
    // Выбранный студент
    private int $studentId;

    public function __construct(Database $db, int $studentId)
    {
        // Передаём базу родителю
        parent::__construct($db);
        $this->studentId = $studentId;
    }

    protected function getTitle(): string
    {
        return 'Средний балл студента по предметам';
    }

    protected function getColumns(): array
    {
        return ['subject' => 'Предмет', 'avg_grade' => 'Средний балл'];
    }

    protected function getQuery(): array
    {
        $sql = 'SELECT sub.title AS subject, AVG(g.grade) AS avg_grade
            FROM grades g
            JOIN subjects sub ON sub.id = g.subject_id
            WHERE g.student_id = :student_id
            GROUP BY sub.id
            ORDER BY sub.title';

        return [$sql, ['student_id' => $this->studentId]];
    }
}

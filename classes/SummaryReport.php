<?php

require_once __DIR__ . '/Report.php';

/**
 * Сводная ведомость по группе и предмету.
 */
class SummaryReport extends Report
{
    // Выбранная группа
    private string $group;
    // Выбранный предмет
    private int $subjectId;

    public function __construct(Database $db, string $group, int $subjectId)
    {
        // Передаём базу родителю
        parent::__construct($db);
        $this->group = $group;
        $this->subjectId = $subjectId;
    }

    protected function getTitle(): string
    {
        return 'Сводная ведомость';
    }

    protected function getColumns(): array
    {
        return [
            'full_name' => 'Студент',
            'grades' => 'Оценки',
            'avg_grade' => 'Средний балл',
            'misses' => 'Пропуски',
        ];
    }

    protected function getQuery(): array
    {
        $sql = "SELECT s.full_name,
                (SELECT GROUP_CONCAT(g.grade, ' ') FROM grades g
                    WHERE g.student_id = s.id AND g.subject_id = :s1) AS grades,
                (SELECT AVG(g.grade) FROM grades g
                    WHERE g.student_id = s.id AND g.subject_id = :s2) AS avg_grade,
                (SELECT COUNT(*) FROM attendance a
                    WHERE a.student_id = s.id AND a.subject_id = :s3 AND a.status = 'Н') AS misses
            FROM students s
            WHERE s.group_name = :group
            ORDER BY s.full_name";

        // Предмет нужен в трёх подзапросах
        $params = [
            's1' => $this->subjectId,
            's2' => $this->subjectId,
            's3' => $this->subjectId,
            'group' => $this->group,
        ];

        return [$sql, $params];
    }
}

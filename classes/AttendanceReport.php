<?php

require_once __DIR__ . '/Report.php';

/**
 * Количество пропусков по студентам.
 */
class AttendanceReport extends Report
{
    protected function getTitle(): string
    {
        return 'Количество пропусков по студентам';
    }

    protected function getColumns(): array
    {
        return ['full_name' => 'Студент', 'misses' => 'Пропусков'];
    }

    protected function getQuery(): array
    {
        $sql = "SELECT s.full_name, COUNT(*) AS misses
            FROM attendance a
            JOIN students s ON s.id = a.student_id
            WHERE a.status = 'Н'
            GROUP BY s.id
            ORDER BY misses DESC";

        return [$sql, []];
    }
}

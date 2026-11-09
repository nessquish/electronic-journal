<?php

/**
 * Работа с таблицей посещаемости.
 */
class Attendance
{
    // Подключение к базе
    private Database $db;
    // Кто выполняет действия
    private string $userLogin;

    public function __construct(Database $db, string $userLogin)
    {
        $this->db = $db;
        $this->userLogin = $userLogin;
    }

    /**
     * Добавляет запись и сохраняет действие в журнале, возвращает id записи.
     */
    public function addRecord(int $studentId, int $subjectId, string $date, string $status): int
    {
        // Сохраняем запись
        $this->db->query(
            'INSERT INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?)',
            [$studentId, $subjectId, $date, $status]
        );
        // Номер новой записи
        $id = $this->db->lastInsertId();

        // Записываем действие
        $this->writeLog("Добавлена запись посещаемости #$id, статус $status");
        return $id;
    }

    private function writeLog(string $action): void
    {
        // Добавляем строку в логи
        $this->db->query(
            'INSERT INTO logs (user_login, action, created_at) VALUES (?, ?, ?)',
            [$this->userLogin, $action, date('d.m.Y H:i:s')]
        );
    }
}

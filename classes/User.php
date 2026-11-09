<?php

/**
 * Пользователь журнала. Данные закрыты и доступны только через методы.
 */
class User
{
    // Все свойства закрыты
    private int $id;
    private string $login;
    private string $passwordHash;
    private string $role;
    private ?int $studentId;

    public function __construct(array $data)
    {
        // Заполняем объект из строки базы
        $this->id = (int) $data['id'];
        $this->login = $data['login'];
        $this->passwordHash = $data['password_hash'];
        $this->role = $data['role'];
        $this->studentId = $data['student_id'] === null ? null : (int) $data['student_id'];
    }

    /**
     * Ищет пользователя по логину, возвращает объект или null.
     */
    public static function findByLogin(string $login, Database $db): ?self
    {
        // Ищем строку в таблице
        $data = $db->query('SELECT * FROM users WHERE login = ?', [$login])->fetch();

        // Нет строки значит нет пользователя
        return $data ? new self($data) : null;
    }

    public function verifyPassword(string $password): bool
    {
        // Сравниваем пароль с хэшем
        return password_verify($password, $this->passwordHash);
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Данные для сессии. Хэш пароля сюда намеренно не входит.
     */
    public function toSession(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'role' => $this->role,
            'student_id' => $this->studentId,
        ];
    }
}

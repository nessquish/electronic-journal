<?php
// Подключение к базе и создание таблиц при первом запуске

// Подключаем класс базы
require_once __DIR__ . '/classes/Database.php';

// Единое подключение
$db = new Database();
$pdo = $db->getPdo(); // для страниц, ещё не переведённых на классы

// Создаём таблицы, если их нет
$pdo->exec("
CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    group_name TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS grades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    grade INTEGER NOT NULL CHECK(grade BETWEEN 2 AND 5),
    date TEXT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'teacher', 'student')),
    student_id INTEGER,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    date TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('Н', 'Б', 'У')),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);
CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_login TEXT NOT NULL,
    action TEXT NOT NULL,
    created_at TEXT NOT NULL
);
");

// Тестовые данные добавляются один раз, если пользователей ещё нет
// Заполняем пустую базу
if ($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() == 0) {
    // Всё или ничего
    $pdo->beginTransaction();

    // Студенты
    $stmt = $pdo->prepare('INSERT INTO students (full_name, group_name) VALUES (?, ?)');
    $students = [
        ['Иванов Иван', 'ИС-21'],
        ['Петрова Анна', 'ИС-21'],
        ['Сидоров Олег', 'ИС-21'],
        ['Кузнецова Мария', 'ИС-22'],
        ['Смирнов Павел', 'ИС-22'],
    ];
    foreach ($students as $row) {
        $stmt->execute($row);
    }

    // Предметы
    $stmt = $pdo->prepare('INSERT INTO subjects (title) VALUES (?)');
    foreach (['Математика', 'Программирование', 'Базы данных'] as $title) {
        $stmt->execute([$title]);
    }

    // По три оценки у каждого студента по каждому предмету
    // Оценки
    $stmt = $pdo->prepare('INSERT INTO grades (student_id, subject_id, grade, date) VALUES (?, ?, ?, ?)');
    for ($student = 1; $student <= 5; $student++) {
        for ($subject = 1; $subject <= 3; $subject++) {
            for ($day = 1; $day <= 3; $day++) {
                $grade = 2 + ($student * 3 + $subject * 5 + $day * 7) % 4;
                if ($student == 2) {
                    $grade = min($grade, 3); // студент с низким баллом для уведомления
                }
                $stmt->execute([$student, $subject, $grade, "2026-09-0$day"]);
            }
        }
    }

    // Записи о посещаемости
    $stmt = $pdo->prepare('INSERT INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?)');
    $rows = [
        [1, 1, '2026-09-01', 'Н'],
        [2, 1, '2026-09-01', 'Н'],
        [2, 2, '2026-09-02', 'Н'],
        [3, 3, '2026-09-02', 'Б'],
        [4, 2, '2026-09-03', 'У'],
    ];
    foreach ($rows as $row) {
        $stmt->execute($row);
    }

    // Пользователи: в базе хранятся только хэши паролей
    $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role, student_id) VALUES (?, ?, ?, ?)');
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', null]);
    $stmt->execute(['teacher', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher', null]);
    $stmt->execute(['ivanov', password_hash('student123', PASSWORD_DEFAULT), 'student', 1]);
    $stmt->execute(['petrova', password_hash('student123', PASSWORD_DEFAULT), 'student', 2]);

    // Сохраняем всё разом
    $pdo->commit();
}

<?php
// Сессия, проверка ролей, защита форм и общие функции

// Часовой пояс Новосибирска
date_default_timezone_set('Asia/Novosibirsk');
// Своё имя Cookie сессии
session_name('JOURNAL2');
// Запускаем сессию
session_start();
// Подключаем базу
require_once __DIR__ . '/db.php';

const LOW_AVG = 3.5;  // ниже этого среднего балла показывается уведомление
const PER_PAGE = 10;  // записей на странице

// Переключение темы, значение хранится в Cookie
if (isset($_GET['theme'])) {
    setcookie('theme', $_GET['theme'] === 'dark' ? 'dark' : 'light', time() + 86400 * 30);
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

// Перейти на другую страницу
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Остановить работу с кодом ошибки
function abort(int $code, string $message): void {
    http_response_code($code);
    exit($message);
}

// Форма отправлена методом POST?
function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Показать ошибку, если она есть
function show_error(string $error): void {
    if ($error !== '') {
        echo '<p class="error">' . e($error) . '</p>';
    }
}

// Безопасный вывод в HTML
function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// Только для вошедших пользователей
function require_login(): void {
    // Гость идёт на вход
    if (!current_user()) {
        redirect('login.php');
    }
}

// Только для указанных ролей
function require_role(array $roles): void {
    require_login();
    // Роль не подходит
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        abort(403, 'Доступ запрещён');
    }
}

function can_edit(): bool {
    return in_array(current_user()['role'] ?? '', ['admin', 'teacher'], true);
}

// Скрытое поле с CSRF-токеном для форм
function csrf_field(): string {
    // Создаём токен один раз
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

// Проверка CSRF-токена при отправке формы
function check_csrf(): void {
    // Токен не совпал
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        abort(400, 'Ошибка CSRF');
    }
}

// Проверка даты в формате ГГГГ-ММ-ДД
function valid_date(string $date): bool {
    // Разбираем дату по шаблону
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    // Дата настоящая, если не изменилась
    return $parsed && $parsed->format('Y-m-d') === $date;
}

// Запись в журнал действий
function add_log(string $action): void {
    global $pdo;
    // Добавляем строку в логи
    $stmt = $pdo->prepare('INSERT INTO logs (user_login, action, created_at) VALUES (?, ?, ?)');
    $stmt->execute([current_user()['login'] ?? 'гость', $action, date('d.m.Y H:i:s')]);
}

function all_students(): array {
    global $pdo;
    return $pdo->query('SELECT id, full_name, group_name FROM students ORDER BY full_name')->fetchAll();
}

function all_subjects(): array {
    global $pdo;
    return $pdo->query('SELECT id, title FROM subjects ORDER BY title')->fetchAll();
}

function all_groups(): array {
    global $pdo;
    return $pdo->query('SELECT DISTINCT group_name FROM students ORDER BY group_name')->fetchAll(PDO::FETCH_COLUMN);
}

// Проверка, что запись с таким id существует
function exists_in(string $table, int $id): bool {
    global $pdo;
    // Имя таблицы только из списка
    $tables = ['students', 'subjects'];
    if (!in_array($table, $tables, true)) {
        return false;
    }
    // Ищем запись по номеру
    $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

// Условия выборки оценок с учётом фильтров и прав пользователя
function grades_filter(): array {
    // Части условия WHERE
    $where = [];
    $params = [];

    // Студент всегда видит только свои оценки
    if (current_user()['role'] === 'student') {
        $where[] = 'g.student_id = :me';
        $params['me'] = current_user()['student_id'];
    }
    // Фильтр по группе
    if (!empty($_GET['group'])) {
        $where[] = 's.group_name = :group';
        $params['group'] = $_GET['group'];
    }
    // Фильтр по предмету
    if (!empty($_GET['subject'])) {
        $where[] = 'g.subject_id = :subject';
        $params['subject'] = (int) $_GET['subject'];
    }
    // Фильтр по дате
    if (!empty($_GET['date']) && valid_date($_GET['date'])) {
        $where[] = 'g.date = :date';
        $params['date'] = $_GET['date'];
    }

    // Склеиваем условия
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

// Сортировка: в запрос попадают только значения из этого списка
function grades_order(): string {
    // Разрешённые поля
    $columns = ['name' => 's.full_name', 'subject' => 'sub.title', 'grade' => 'g.grade', 'date' => 'g.date'];
    $column = $columns[$_GET['sort'] ?? 'date'] ?? 'g.date';
    $direction = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    return "ORDER BY $column $direction, g.id DESC";
}

// Текущий адрес с изменёнными параметрами
function url_with(array $changes): string {
    return '?' . http_build_query(array_merge($_GET, $changes));
}

// Шапка страницы с меню
function page_header(string $title): void {
    // Данные для шапки
    $user = current_user();
    // Выбранная тема
    $dark = ($_COOKIE['theme'] ?? '') === 'dark';
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: <?= $dark ? '#222' : '#fff' ?>; color: <?= $dark ? '#eee' : '#222' ?>; }
        a { color: <?= $dark ? '#8cf' : '#06c' ?>; }
        nav a { margin-right: 12px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #888; padding: 5px 10px; text-align: left; }
        form.inline { display: inline; }
        .warning { color: #c00; font-weight: bold; }
        .error { color: #c00; }
    </style>
</head>
<body>
    <?php if ($user): ?>
    <nav>
        <a href="index.php">Главная</a>
        <a href="grades.php">Оценки</a>
        <a href="attendance.php">Посещаемость</a>
        <?php if (can_edit()): ?><a href="reports.php">Отчёты</a><?php endif; ?>
        <?php if ($user['role'] === 'admin'): ?><a href="users.php">Пользователи</a><?php endif; ?>
        <a href="?theme=<?= $dark ? 'light' : 'dark' ?>"><?= $dark ? 'Светлая тема' : 'Тёмная тема' ?></a>
        <a href="logout.php">Выйти (<?= e($user['login']) ?>)</a>
    </nav>
    <hr>
    <?php endif; ?>
    <h1><?= e($title) ?></h1>
    <?php
}

function page_footer(): void {
    echo "</body>\n</html>\n";
}

// Поля формы оценки, общие для добавления и редактирования
function grade_fields(array $grade): void {
    ?>
    <p>Студент:<br>
        <select name="student_id">
            <?php foreach (all_students() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $grade['student_id'] ? 'selected' : '' ?>><?= e($s['full_name'] . ' (' . $s['group_name'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>Предмет:<br>
        <select name="subject_id">
            <?php foreach (all_subjects() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $grade['subject_id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>Оценка (2–5):<br><input type="number" name="grade" min="2" max="5" value="<?= e($grade['grade']) ?>"></p>
    <p>Дата:<br><input type="date" name="date" value="<?= e($grade['date']) ?>"></p>
    <?php
}

// Проверка данных оценки, возвращает текст ошибки или пустую строку
function grade_error(array $data): string {
    // Студент и предмет должны существовать
    if (!exists_in('students', (int) $data['student_id']) || !exists_in('subjects', (int) $data['subject_id'])) {
        return 'Выберите студента и предмет';
    }
    // Оценка только целая от 2 до 5
    if (filter_var($data['grade'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 5]]) === false) {
        return 'Оценка должна быть от 2 до 5';
    }
    // Дата должна быть настоящей
    if (!valid_date((string) $data['date'])) {
        return 'Некорректная дата';
    }
    return '';
}

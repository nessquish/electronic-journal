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
    // Ошибку показываем в оформлении журнала
    html_start('Ошибка ' . $code);
    echo '<div class="login"><div class="login-card"><h1>Ошибка ' . $code . '</h1><p>' . e($message) . '</p>'
        . '<a class="btn" href="index.php">На главную</a></div></div></body></html>';
    exit;
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

// Значки меню
function icon(string $name): string {
    $paths = [
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
    ];
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

// Значок раскрытого журнала
function journal_icon(): string {
    return '<svg class="mark" width="40" height="40" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M24 12C19 8 12 7 5 8v30c7-1 14 0 19 4 5-4 12-5 19-4V8c-7-1-14 0-19 4z" fill="var(--mark)"/>'
        . '<path d="M24 12v30M11 16c3 0 6 .5 8 1.5M11 23c3 0 6 .5 8 1.5M11 30c3 0 6 .5 8 1.5M37 16c-3 0-6 .5-8 1.5M37 23c-3 0-6 .5-8 1.5M37 30c-3 0-6 .5-8 1.5"/>'
        . '</svg>';
}

// Включена ли тёмная тема
function is_dark(): bool {
    return ($_COOKIE['theme'] ?? '') === 'dark';
}

// Ссылка смены темы
function theme_link(): string {
    $dark = is_dark();
    return '<a class="nav" href="?theme=' . ($dark ? 'light' : 'dark') . '">'
        . icon($dark ? 'sun' : 'moon') . ($dark ? 'Светлая тема' : 'Тёмная тема') . '</a>';
}

// Общее начало HTML
function html_start(string $title): void {
    ?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?= is_dark() ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php
}

// Шапка страницы: боковое меню и заголовок
function page_header(string $title, string $subtitle = '', string $actions = ''): void {
    $user = current_user();
    html_start($title);

    // Гостю показываем только карточку входа
    if (!$user) {
        echo '<div class="login"><div class="login-card"><div><h1>' . e($title) . '</h1><p class="sub">' . e($subtitle) . '</p></div>';
        return;
    }

    // Пункты меню зависят от роли
    $menu = ['index.php' => 'Главная', 'grades.php' => 'Оценки', 'attendance.php' => 'Посещаемость'];
    if (can_edit()) {
        $menu['reports.php'] = 'Отчёты';
    }
    if ($user['role'] === 'admin') {
        $menu['users.php'] = 'Пользователи';
    }

    // Страницы оценки подсвечивают пункт «Оценки»
    $page = basename($_SERVER['SCRIPT_NAME']);
    if (in_array($page, ['add_grade.php', 'edit_grade.php'], true)) {
        $page = 'grades.php';
    }
    ?>
    <div class="shell">
        <aside class="side">
            <div class="logo"><?= journal_icon() ?><span>Электронный журнал</span></div>
            <?php foreach ($menu as $url => $name): ?>
                <a class="nav <?= $url === $page ? 'on' : '' ?>" href="<?= $url ?>"><?= $name ?></a>
            <?php endforeach; ?>
            <?php // Тема и выход внизу панели ?>
            <div class="side-bottom">
                <?= theme_link() ?>
                <a class="nav" href="logout.php">Выйти (<?= e($user['login']) ?>)</a>
            </div>
        </aside>
        <main class="main">
            <div class="head">
                <div>
                    <h1><?= e($title) ?></h1>
                    <?php if ($subtitle !== ''): ?><p class="sub"><?= e($subtitle) ?></p><?php endif; ?>
                </div>
                <div class="row"><?= $actions ?></div>
            </div>
    <?php
}

// Конец страницы
function page_footer(): void {
    if (current_user()) {
        echo "</main></div>\n";
    } else {
        echo "</div></div>\n";
    }
    echo "<script src=\"assets/app.js\"></script>\n</body>\n</html>\n";
}

// Данные студента, который вошёл
function current_student(): array {
    global $db;
    $id = current_user()['student_id'];
    return $db->query('SELECT full_name, group_name FROM students WHERE id = ?', [$id])->fetch() ?: ['full_name' => '', 'group_name' => ''];
}

// Цветная метка среднего балла
function average_pill(float $average): string {
    $class = $average < 3 ? 'low' : ($average < LOW_AVG ? 'warn' : 'good');
    return '<span class="pill ' . $class . '">' . number_format($average, 2) . '</span>';
}

// Кружок с оценкой
function grade_badge($grade): string {
    $value = (int) $grade;
    return '<span class="g g' . $value . '">' . $value . '</span>';
}

// Цвет метки посещаемости
function status_class(string $status): string {
    return ['Н' => 'low', 'Б' => 'warn', 'У' => 'ok'][$status] ?? 'ok';
}

// Поля формы оценки, общие для добавления и редактирования
function grade_fields(array $grade): void {
    ?>
    <p>Студент<br>
        <select name="student_id">
            <?php foreach (all_students() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $grade['student_id'] ? 'selected' : '' ?>><?= e($s['full_name'] . ' (' . $s['group_name'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>Предмет<br>
        <select name="subject_id">
            <?php foreach (all_subjects() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $grade['subject_id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>Оценка (2–5)<br>
        <select name="grade">
            <option value="">Выберите оценку</option>
            <?php foreach ([5, 4, 3, 2] as $value): ?>
                <option value="<?= $value ?>" <?= (string) $value === (string) $grade['grade'] ? 'selected' : '' ?>><?= $value ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>Дата<br><input type="date" name="date" value="<?= e($grade['date']) ?>"></p>
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

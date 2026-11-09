<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Страница только для администратора
require_role(['admin']);

// Пока ошибки нет
$error = '';

// Форма отправлена
if (is_post()) {
    // Проверяем токен формы
    check_csrf();

    // Нажата кнопка удаления
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['delete'];
        // Себя удалять нельзя
        if ($id === (int) current_user()['id']) {
            $error = 'Нельзя удалить свою учётную запись';
        } else {
            // Удаляем пользователя
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            add_log("Удалён пользователь #$id");
            redirect('users.php');
        }
    } else {
        // Данные нового пользователя
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $student_id = (int) ($_POST['student_id'] ?? 0);

        // Проверяем, свободен ли логин
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE login = ?');
        $stmt->execute([$login]);

        // Логин и пароль обязательны
        if ($login === '' || strlen($password) < 6) {
            $error = 'Укажите логин и пароль не короче 6 символов';
        } elseif (!in_array($role, ['admin', 'teacher', 'student'], true)) {
            $error = 'Некорректная роль';
        } elseif ($role === 'student' && !exists_in('students', $student_id)) {
            $error = 'Для роли student выберите студента';
        } elseif ($stmt->fetchColumn()) {
            $error = 'Такой логин уже есть';
        } else {
            // Сохраняем только хэш пароля
            $stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role, student_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$login, password_hash($password, PASSWORD_DEFAULT), $role, $role === 'student' ? $student_id : null]);
            add_log("Добавлен пользователь $login");
            redirect('users.php');
        }
    }
}

// Список пользователей
$users = $pdo->query('SELECT u.id, u.login, u.role, s.full_name FROM users u LEFT JOIN students s ON s.id = u.student_id ORDER BY u.login')->fetchAll();
// Последние действия
$logs = $pdo->query('SELECT user_login, action, created_at FROM logs ORDER BY id DESC LIMIT 30')->fetchAll();

// Начинаем вывод страницы
page_header('Пользователи');
?>
<?php show_error($error); ?>

<?php // Форма добавления ?>
<h2>Добавить пользователя</h2>
<form method="post">
    <?= csrf_field() ?>
    <input type="text" name="login" placeholder="Логин">
    <input type="password" name="password" placeholder="Пароль">
    <select name="role">
        <option>student</option>
        <option>teacher</option>
        <option>admin</option>
    </select>
    <select name="student_id">
        <option value="">Студент (для роли student)</option>
        <?php foreach (all_students() as $s): ?>
            <option value="<?= $s['id'] ?>"><?= e($s['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Добавить</button>
</form>

<?php // Таблица пользователей ?>
<h2>Все пользователи</h2>
<table>
    <tr><th>Логин</th><th>Роль</th><th>Студент</th><th>Действия</th></tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['login']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td>
                <form class="inline" method="post" onsubmit="return confirm('Удалить пользователя?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete" value="<?= (int) $u['id'] ?>">
                    <button type="submit">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php // Журнал действий ?>
<h2>Журнал действий (последние 30)</h2>
<table>
    <tr><th>Время</th><th>Пользователь</th><th>Действие</th></tr>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= e($log['created_at']) ?></td>
            <td><?= e($log['user_login']) ?></td>
            <td><?= e($log['action']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_footer(); ?>

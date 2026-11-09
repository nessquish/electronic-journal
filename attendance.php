<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Подключаем класс посещаемости
require_once 'classes/Attendance.php';
// Страница только для вошедших
require_login();

// Допустимые статусы
$statuses = ['Н' => 'Отсутствовал', 'Б' => 'Болел', 'У' => 'Уважительная причина'];
// Пока ошибки нет
$error = '';
// Значения формы по умолчанию
$form = ['student_id' => '', 'subject_id' => '', 'date' => date('Y-m-d'), 'status' => 'Н'];
// Ноль значит режим добавления
$edit_id = 0;

// Запись для редактирования
if (isset($_GET['edit'])) {
    // Редактировать могут не все
    require_role(['admin', 'teacher']);
    $edit_id = (int) $_GET['edit'];
    // Загружаем запись
    $stmt = $pdo->prepare('SELECT student_id, subject_id, date, status FROM attendance WHERE id = ?');
    $stmt->execute([$edit_id]);
    $form = $stmt->fetch();
    // Такой записи нет
    if (!$form) {
        abort(404, 'Запись не найдена');
    }
}

// Форма отправлена
if (is_post()) {
    // Студент не может менять записи
    require_role(['admin', 'teacher']);
    // Проверяем токен формы
    check_csrf();

    // Нажата кнопка удаления
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['delete'];
        $stmt = $pdo->prepare('DELETE FROM attendance WHERE id = ?');
        $stmt->execute([$id]);
        add_log("Удалена запись посещаемости #$id");
        redirect('attendance.php');
    }

    // Берём из формы только нужные поля
    $form = array_merge($form, array_intersect_key($_POST, $form));

    // Проверяем введённые данные
    if (!exists_in('students', (int) $form['student_id']) || !exists_in('subjects', (int) $form['subject_id'])) {
        $error = 'Выберите студента и предмет';
    } elseif (!valid_date((string) $form['date'])) {
        $error = 'Некорректная дата';
    } elseif (!isset($statuses[$form['status']])) {
        $error = 'Некорректный статус';
    } elseif ($edit_id) {
        // Сохраняем изменения записи
        $stmt = $pdo->prepare('UPDATE attendance SET student_id = :student_id, subject_id = :subject_id, date = :date, status = :status WHERE id = :id');
        $stmt->execute($form + ['id' => $edit_id]);
        add_log("Изменена запись посещаемости #$edit_id");
        redirect('attendance.php');
    } else {
        // Добавляем новую запись через класс
        $attendance = new Attendance($db, current_user()['login']);
        $attendance->addRecord((int) $form['student_id'], (int) $form['subject_id'], $form['date'], $form['status']);
        redirect('attendance.php');
    }
}

// Студент видит только свои записи
$where = '';
$params = [];
if (current_user()['role'] === 'student') {
    $where = 'WHERE a.student_id = :me';
    $params['me'] = current_user()['student_id'];
}
// Загружаем записи для таблицы
$stmt = $pdo->prepare("SELECT a.id, s.full_name, s.group_name, sub.title, a.date, a.status
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    JOIN subjects sub ON sub.id = a.subject_id
    $where
    ORDER BY a.date DESC, a.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Начинаем вывод страницы
page_header('Посещаемость');
?>
<?php show_error($error); ?>

<?php // Форма только для админа и преподавателя ?>
<?php if (can_edit()): ?>
    <h2><?= $edit_id ? 'Изменить запись' : 'Добавить запись' ?></h2>
    <form method="post">
        <?= csrf_field() ?>
        <select name="student_id">
            <?php foreach (all_students() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $form['student_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subject_id">
            <?php foreach (all_subjects() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $form['subject_id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?= e($form['date']) ?>">
        <select name="status">
            <?php foreach ($statuses as $code => $title): ?>
                <option value="<?= $code ?>" <?= $code === $form['status'] ? 'selected' : '' ?>><?= $code ?> — <?= $title ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Сохранить</button>
        <?php if ($edit_id): ?><a href="attendance.php">Отмена</a><?php endif; ?>
    </form>
<?php endif; ?>

<?php // Таблица посещаемости ?>
<h2>Записи</h2>
<table>
    <tr>
        <th>ФИО</th><th>Группа</th><th>Предмет</th><th>Дата</th><th>Статус</th>
        <?php if (can_edit()): ?><th>Действия</th><?php endif; ?>
    </tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['group_name']) ?></td>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['date']) ?></td>
            <td><?= e($row['status']) ?> — <?= e($statuses[$row['status']]) ?></td>
            <?php if (can_edit()): ?>
                <td>
                    <a href="?edit=<?= (int) $row['id'] ?>">Изменить</a>
                    <form class="inline" method="post" action="attendance.php" onsubmit="return confirm('Удалить запись?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete" value="<?= (int) $row['id'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
</table>
<?php page_footer(); ?>

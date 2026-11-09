<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Оценки меняют только админ и преподаватель
require_role(['admin', 'teacher']);

// Номер оценки из адреса
$id = (int) ($_GET['id'] ?? 0);
// Загружаем оценку из базы
$stmt = $pdo->prepare('SELECT student_id, subject_id, grade, date FROM grades WHERE id = ?');
$stmt->execute([$id]);
$grade = $stmt->fetch();

// Такой оценки нет
if (!$grade) {
    abort(404, 'Оценка не найдена');
}

// Пока ошибки нет
$error = '';

// Форма отправлена
if (is_post()) {
    // Проверяем токен формы
    check_csrf();
    // Берём из формы только нужные поля
    $grade = array_merge($grade, array_intersect_key($_POST, $grade));
    // Проверяем введённые данные
    $error = grade_error($grade);

    // Ошибок нет, можно сохранять
    if ($error === '') {
        // Обновляем оценку в базе
        $stmt = $pdo->prepare('UPDATE grades SET student_id = :student_id, subject_id = :subject_id, grade = :grade, date = :date WHERE id = :id');
        $stmt->execute($grade + ['id' => $id]);
        // Записываем действие
        add_log("Изменена оценка #$id");
        // Возвращаемся к списку
        redirect('grades.php');
    }
}

// Начинаем вывод страницы
page_header('Изменить оценку');
?>
<?php show_error($error); ?>
<form method="post">
    <?= csrf_field() ?>
    <?php grade_fields($grade); ?>
    <button type="submit">Сохранить</button>
    <a href="grades.php">Отмена</a>
</form>
<?php page_footer(); ?>

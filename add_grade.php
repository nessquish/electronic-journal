<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Оценки добавляют только админ и преподаватель
require_role(['admin', 'teacher']);

// Пока ошибки нет
$error = '';
// Значения формы по умолчанию
$grade = ['student_id' => '', 'subject_id' => '', 'grade' => '', 'date' => date('Y-m-d')];

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
        // Добавляем оценку в базу
        $stmt = $pdo->prepare('INSERT INTO grades (student_id, subject_id, grade, date) VALUES (:student_id, :subject_id, :grade, :date)');
        $stmt->execute($grade);
        // Записываем действие
        add_log('Добавлена оценка #' . $pdo->lastInsertId());
        // Возвращаемся к списку
        redirect('grades.php');
    }
}

// Начинаем вывод страницы
page_header('Добавить оценку');
?>
<?php show_error($error); ?>
<form method="post">
    <?= csrf_field() ?>
    <?php grade_fields($grade); ?>
    <button type="submit">Сохранить</button>
    <a href="grades.php">Отмена</a>
</form>
<?php page_footer(); ?>

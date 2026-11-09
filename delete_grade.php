<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Оценки удаляют только админ и преподаватель
require_role(['admin', 'teacher']);

// Удаление выполняется только методом POST с CSRF-токеном
if (!is_post()) {
    abort(405, 'Метод не разрешён');
}
// Проверяем токен формы
check_csrf();

// Номер удаляемой оценки
$id = (int) ($_POST['id'] ?? 0);
// Удаляем оценку из базы
$stmt = $pdo->prepare('DELETE FROM grades WHERE id = ?');
$stmt->execute([$id]);
// Записываем действие
add_log("Удалена оценка #$id");

// Возвращаемся к списку
redirect('grades.php');

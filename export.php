<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Выгрузка только для вошедших
require_login();

// Те же условия, что и в журнале: учитываются права и фильтры
[$where, $params] = grades_filter();
// Загружаем все подходящие оценки
$stmt = $pdo->prepare("SELECT s.full_name, sub.title, g.grade, g.date
    FROM grades g
    JOIN students s ON s.id = g.student_id
    JOIN subjects sub ON sub.id = g.subject_id
    $where " . grades_order());
$stmt->execute($params);

// Записываем действие
add_log('Экспорт оценок в CSV');

// Сообщаем браузеру, что это файл
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="journal.csv"');

// Пишем прямо в ответ браузеру
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // метка UTF-8, чтобы Excel показал кириллицу
// Заголовки колонок
fputcsv($out, ['Студент', 'Предмет', 'Оценка', 'Дата'], ';', '"', '');

// Строки с данными
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, $row, ';', '"', '');
}
// Закрываем поток
fclose($out);

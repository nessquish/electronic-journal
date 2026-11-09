<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Подключаем классы отчётов
require_once 'classes/StudentAverageReport.php';
require_once 'classes/GroupAverageReport.php';
require_once 'classes/AttendanceReport.php';
require_once 'classes/SummaryReport.php';
// Отчёты закрыты для студентов
require_role(['admin', 'teacher']);

// Данные для списков фильтра
$students = all_students();
$subjects = all_subjects();
$groups = all_groups();

// Выбранные значения или первые из списка
$student_id = (int) ($_GET['student_id'] ?? ($students[0]['id'] ?? 0));
$group = $_GET['group'] ?? ($groups[0] ?? '');
$subject_id = (int) ($_GET['subject_id'] ?? ($subjects[0]['id'] ?? 0));

// Разные классы отчётов в одном списке
$reports = [
    new StudentAverageReport($db, $student_id),
    new GroupAverageReport($db),
    new AttendanceReport($db),
    new SummaryReport($db, $group, $subject_id),
];

// Полиморфизм: функция знает только об интерфейсе Reportable
function render_report(Reportable $report): void
{
    // Отчёт сам знает, как себя собрать
    $data = $report->generate();

    echo '<h2>' . e($data['title']) . '</h2><table><tr>';
    // Заголовки таблицы
    foreach ($data['columns'] as $title) {
        echo '<th>' . e($title) . '</th>';
    }
    echo "</tr>\n";

    // Строки таблицы
    foreach ($data['rows'] as $row) {
        echo '<tr>';
        foreach ($data['columns'] as $key => $title) {
            echo '<td>' . e($row[$key]) . '</td>';
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Начинаем вывод страницы
page_header('Отчёты');
?>
<?php // Фильтры отчётов ?>
<form method="get">
    Студент:
    <select name="student_id">
        <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $s['id'] == $student_id ? 'selected' : '' ?>><?= e($s['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    Группа:
    <select name="group">
        <?php foreach ($groups as $g): ?>
            <option <?= $g === $group ? 'selected' : '' ?>><?= e($g) ?></option>
        <?php endforeach; ?>
    </select>
    Предмет:
    <select name="subject_id">
        <?php foreach ($subjects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $s['id'] == $subject_id ? 'selected' : '' ?>><?= e($s['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Показать</button>
</form>

<?php
// Выводим все отчёты одним циклом
foreach ($reports as $report) {
    render_report($report);
}
page_footer();

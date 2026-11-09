<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Страница только для вошедших
require_login();

// Текущий пользователь
$user = current_user();

if ($user['role'] === 'student') {
    // Личный кабинет студента
    // Средний балл студента по предметам
    $stmt = $pdo->prepare('
        SELECT sub.title AS subject, AVG(g.grade) AS avg_grade, COUNT(*) AS total
        FROM grades g
        JOIN subjects sub ON sub.id = g.subject_id
        WHERE g.student_id = :student_id
        GROUP BY sub.id
        ORDER BY sub.title
    ');
    $stmt->execute(['student_id' => $user['student_id']]);
    // Получаем строки отчёта
    $averages = $stmt->fetchAll();

    // Считаем пропуски студента
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'Н'");
    $stmt->execute([$user['student_id']]);
    // Получаем число пропусков
    $misses = $stmt->fetchColumn();
} else {
    // Для преподавателя и администратора: студенты с низким средним баллом
    // Ищем студентов ниже порога
    $stmt = $pdo->prepare('
        SELECT s.full_name, s.group_name, AVG(g.grade) AS avg_grade
        FROM grades g
        JOIN students s ON s.id = g.student_id
        GROUP BY s.id
        HAVING AVG(g.grade) < CAST(:limit AS REAL)
        ORDER BY avg_grade
    ');
    $stmt->execute(['limit' => LOW_AVG]);
    // Получаем список
    $low = $stmt->fetchAll();
}

// Заголовок зависит от роли
// Подзаголовок зависит от роли
if ($user['role'] === 'student') {
    $student = current_student();
    $subtitle = $student['full_name'] . ', группа ' . $student['group_name'];
} else {
    $subtitle = 'Вы вошли как ' . $user['login'];
}
page_header('Главная', $subtitle);
?>
<?php if ($user['role'] === 'student'): ?>
    <?php
    // Итоги по всем предметам
    $count = array_sum(array_column($averages, 'total'));
    $sum = 0;
    foreach ($averages as $row) {
        $sum += $row['avg_grade'] * $row['total'];
    }
    // Предметы с низким баллом
    $weak = array_column(array_filter($averages, fn($row) => $row['avg_grade'] < LOW_AVG), 'subject');
    ?>
    <?php if ($weak): ?>
        <div class="alert">Низкий средний балл: <?= e(implode(', ', $weak)) ?></div>
    <?php endif; ?>
    <div class="stats">
        <div class="stat"><span>Средний балл</span><b><?= $count ? number_format($sum / $count, 2) : '—' ?></b></div>
        <div class="stat"><span>Оценок</span><b><?= (int) $count ?></b></div>
        <div class="stat"><span>Пропусков (Н)</span><b><?= (int) $misses ?></b></div>
    </div>
    <h2>Средний балл по предметам</h2>
    <div class="wrap"><table>
        <tr><th>Предмет</th><th>Средний балл</th><th>Оценок</th></tr>
        <?php foreach ($averages as $row): ?>
            <tr>
                <td><?= e($row['subject']) ?></td>
                <td><?= average_pill($row['avg_grade']) ?></td>
                <td><?= (int) $row['total'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table></div>
<?php else: ?>
    <?php // Общие показатели журнала ?>
    <div class="stats">
        <?php foreach (['Студентов' => 'students', 'Оценок' => 'grades', 'Предметов' => 'subjects'] as $label => $table): ?>
            <div class="stat"><span><?= $label ?></span><b><?= (int) $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() ?></b></div>
        <?php endforeach; ?>
    </div>
    <h2>Уведомления о низком среднем балле</h2>
    <?php if (!$low): ?>
        <p>Студентов с низким баллом нет.</p>
    <?php endif; ?>
    <?php if ($low): ?>
        <div class="wrap"><table>
            <tr><th>Студент</th><th>Группа</th><th>Средний балл</th></tr>
            <?php foreach ($low as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['group_name']) ?></td>
                    <td><?= average_pill($row['avg_grade']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>

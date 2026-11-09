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
page_header($user['role'] === 'student' ? 'Личный кабинет' : 'Главная');
?>
<p>Вы вошли как <b><?= e($user['login']) ?></b>, роль: <?= e($user['role']) ?></p>

<?php if ($user['role'] === 'student'): ?>
    <h2>Средний балл по предметам</h2>
    <table>
        <tr><th>Предмет</th><th>Средний балл</th><th>Оценок</th></tr>
        <?php foreach ($averages as $row): ?>
            <tr>
                <td><?= e($row['subject']) ?></td>
                <td><?= number_format($row['avg_grade'], 2) ?></td>
                <td><?= (int) $row['total'] ?></td>
            </tr>
            <?php if ($row['avg_grade'] < LOW_AVG): ?>
                <tr><td colspan="3" class="warning">Низкий средний балл по предмету «<?= e($row['subject']) ?>»</td></tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <p>Пропусков (Н): <?= (int) $misses ?></p>
<?php else: ?>
    <h2>Уведомления о низком среднем балле</h2>
    <?php if (!$low): ?>
        <p>Студентов с низким баллом нет.</p>
    <?php endif; ?>
    <?php foreach ($low as $row): ?>
        <p class="warning"><?= e($row['full_name']) ?> (<?= e($row['group_name']) ?>): <?= number_format($row['avg_grade'], 2) ?></p>
    <?php endforeach; ?>
<?php endif; ?>
<?php page_footer(); ?>

<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Подключаем класс оценок
require_once 'classes/Grade.php';
// Страница только для вошедших
require_login();

// Условия поиска с учётом прав
[$where, $params] = grades_filter();
// Объект для работы с оценками
$grades = new Grade($db);

// Количество записей и номер страницы
$total = $grades->count($where, $params);
// Всего страниц, минимум одна
$pages = max(1, (int) ceil($total / PER_PAGE));
// Текущая страница в допустимых границах
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

// Записи текущей страницы
$rows = $grades->getPage($where, $params, grades_order(), PER_PAGE, ($page - 1) * PER_PAGE);

// Ссылка сортировки: повторное нажатие меняет направление
function sort_link(string $key, string $title): string {
    // Определяем новое направление
    $dir = ($_GET['sort'] ?? '') === $key && ($_GET['dir'] ?? '') === 'asc' ? 'desc' : 'asc';
    // Собираем готовую ссылку
    return '<a href="' . e(url_with(['sort' => $key, 'dir' => $dir, 'page' => 1])) . '">' . e($title) . '</a>';
}

// Начинаем вывод страницы
page_header('Оценки');
?>
<?php // Форма фильтров ?>
<form method="get">
    <?php if (can_edit()): ?>
        Группа:
        <select name="group">
            <option value="">Все</option>
            <?php foreach (all_groups() as $group): ?>
                <option <?= ($_GET['group'] ?? '') === $group ? 'selected' : '' ?>><?= e($group) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    Предмет:
    <select name="subject">
        <option value="">Все</option>
        <?php foreach (all_subjects() as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($_GET['subject'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
        <?php endforeach; ?>
    </select>
    Дата: <input type="date" name="date" value="<?= e($_GET['date'] ?? '') ?>">
    <button type="submit">Применить</button>
    <a href="grades.php">Сбросить</a>
</form>

<p>
    Найдено записей: <?= $total ?>.
    <a href="export.php<?= e(url_with(['page' => null])) ?>">Экспорт в CSV</a>
    <?php if (can_edit()): ?> | <a href="add_grade.php">Добавить оценку</a><?php endif; ?>
</p>

<?php // Таблица оценок ?>
<table>
    <tr>
        <th><?= sort_link('name', 'ФИО') ?></th>
        <th>Группа</th>
        <th><?= sort_link('subject', 'Предмет') ?></th>
        <th><?= sort_link('grade', 'Оценка') ?></th>
        <th><?= sort_link('date', 'Дата') ?></th>
        <?php if (can_edit()): ?><th>Действия</th><?php endif; ?>
    </tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['group_name']) ?></td>
            <td><?= e($row['title']) ?></td>
            <td><?= (int) $row['grade'] ?></td>
            <td><?= e($row['date']) ?></td>
            <?php if (can_edit()): ?>
                <td>
                    <a href="edit_grade.php?id=<?= (int) $row['id'] ?>">Изменить</a>
                    <form class="inline" method="post" action="delete_grade.php" onsubmit="return confirm('Удалить оценку?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit">Удалить</button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
</table>

<p>
    <?php // Ссылки на страницы ?>
    Страницы:
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i == $page): ?>
            <b><?= $i ?></b>
        <?php else: ?>
            <a href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</p>
<?php page_footer(); ?>

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

// Список вариантов сортировки
function sort_select(): string {
    $options = [['date', 'desc', 'Дата: сначала новые'], ['date', 'asc', 'Дата: сначала старые']];
    // ФИО нужно только тем, кто видит всех студентов
    if (can_edit()) {
        $options[] = ['name', 'asc', 'ФИО: А–Я'];
        $options[] = ['name', 'desc', 'ФИО: Я–А'];
    }
    $options[] = ['subject', 'asc', 'Предмет: А–Я'];
    $options[] = ['subject', 'desc', 'Предмет: Я–А'];
    $options[] = ['grade', 'desc', 'Оценка: сначала высокие'];
    $options[] = ['grade', 'asc', 'Оценка: сначала низкие'];

    // Текущая сортировка
    $sort = $_GET['sort'] ?? 'date';
    $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $html = '<span>Сортировка:</span><select data-go aria-label="Сортировка">';
    foreach ($options as [$key, $direction, $title]) {
        $selected = $key === $sort && $direction === $dir ? ' selected' : '';
        $url = url_with(['sort' => $key, 'dir' => $direction, 'page' => 1]);
        $html .= '<option value="' . e($url) . '"' . $selected . '>' . e($title) . '</option>';
    }
    return $html . '</select>';
}

// Начинаем вывод страницы
// Кнопки в заголовке страницы
$actions = sort_select() . '<a class="btn alt" href="export.php' . e(url_with(['page' => null])) . '">Экспорт в CSV</a>';
if (can_edit()) {
    $actions .= '<a class="btn" href="add_grade.php">Добавить оценку</a>';
}
if (can_edit()) {
    page_header('Оценки', "Найдено записей: $total", $actions);
} else {
    page_header('Мои оценки', current_student()['full_name'] . ", записей: $total", $actions);
}
?>
<?php // Форма фильтров ?>
<form method="get" class="row">
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
    <button type="submit" class="btn alt">Применить</button>
    <a class="btn alt" href="grades.php">Сбросить</a>
</form>

<?php // Таблица оценок ?>
<div class="wrap"><table>
    <tr>
        <?php if (can_edit()): ?>
            <th>ФИО</th>
            <th>Группа</th>
        <?php endif; ?>
        <th>Предмет</th>
        <th>Оценка</th>
        <th>Дата</th>
        <?php if (can_edit()): ?><th>Действия</th><?php endif; ?>
    </tr>
    <?php foreach ($rows as $row): ?>
        <tr>
            <?php if (can_edit()): ?>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['group_name']) ?></td>
            <?php endif; ?>
            <td><?= e($row['title']) ?></td>
            <td><?= grade_badge($row['grade']) ?></td>
            <td><?= e($row['date']) ?></td>
            <?php if (can_edit()): ?>
                <td>
                    <a class="btn alt" href="edit_grade.php?id=<?= (int) $row['id'] ?>">Изменить</a>
                    <form class="inline" method="post" action="delete_grade.php" onsubmit="return confirm('Удалить оценку?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="bad">Удалить</button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
</table></div>

<p class="pages">
    <?php // Ссылки на страницы ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i == $page): ?>
            <b><?= $i ?></b>
        <?php else: ?>
            <a href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</p>
<?php page_footer(); ?>

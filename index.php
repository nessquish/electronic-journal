<?php
require_once 'db.php';
$pdo = getPDO();

// Добавление студента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $groupId = $_POST['group_id'] ?: null;

    if (!empty($name) && !empty($email)) {
        $stmt = $pdo->prepare("INSERT INTO students (name, email, group_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $groupId]);
    }
    header('Location: index.php');
    exit;
}

// Удаление студента
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: index.php');
    exit;
}

// Список студентов с названием группы (LEFT JOIN)
$stmt = $pdo->query("SELECT s.id, s.name, s.email, g.name AS group_name
                     FROM students s
                     LEFT JOIN groups g ON s.group_id = g.id
                     ORDER BY s.id DESC");
$students = $stmt->fetchAll();

// Список групп
$groups = $pdo->query("SELECT id, name FROM groups ORDER BY name")->fetchAll();

// Статистика по группам
$stats = $pdo->query("SELECT g.name, COUNT(s.id) AS count
                      FROM groups g
                      LEFT JOIN students s ON g.id = s.group_id
                      GROUP BY g.id, g.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Электронный журнал группы</title>

    <!-- Красивый шрифт Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ===== Переливающийся пастельный фон ===== */
        @keyframes bgShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: 'Manrope', 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #234;
        }

        body {
            background: linear-gradient(
                120deg,
                #9ce0d6 0%,
                #8ccdf0 20%,
                #bfe7f7 40%,
                #eaf9ff 50%,
                #a8e8d9 70%,
                #8fd3ee 90%,
                #a8e5df 100%
            );
            background-size: 400% 400%;
            animation: bgShift 12s ease-in-out infinite;
            padding: 30px 20px;
        }

        .page {
            max-width: 1050px;
            margin: 0 auto;
        }

        /* ===== Заголовок страницы ===== */
        h1 {
            text-align: center;
            color: #1a6a8c;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 700;
            font-size: 1.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 2px 8px rgba(120, 200, 220, 0.5);
            margin-bottom: 28px;
        }

        h2 {
            color: #207b9d;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 14px;
            font-size: 1.15rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(6px);
            border: 1px solid #bfe3ec;
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 22px;
            box-shadow: 0 6px 22px rgba(100, 180, 200, 0.2);
        }

        form.add-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        form.add-form input,
        form.add-form select {
            padding: 9px 12px;
            border: 1px solid #b7dde8;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92);
            font-size: 0.95rem;
            color: #234;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        form.add-form input:focus,
        form.add-form select:focus {
            border-color: #5cb8d8;
            box-shadow: 0 0 0 3px rgba(120, 200, 230, 0.4);
        }

        form.add-form input[type="text"]  { flex: 1 1 200px; }
        form.add-form input[type="email"] { flex: 1 1 220px; }
        form.add-form select              { flex: 0 1 200px; }

        button, .btn {
            background: linear-gradient(135deg, #5ab4d8 0%, #3d94c9 100%);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
            box-shadow: 0 3px 10px rgba(80, 160, 200, 0.4);
        }

        button:hover, .btn:hover {
            background: linear-gradient(135deg, #4aa5cc 0%, #2f80b5 100%);
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(80, 160, 200, 0.5);
        }

        button:active, .btn:active {
            transform: translateY(0);
        }

        table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #b8dfea;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 4px 16px rgba(120, 190, 210, 0.2);
        }

        th, td {
            padding: 11px 14px;
            text-align: left;
            border-bottom: 1px solid #e2f1f5;
            font-size: 0.95rem;
        }

        th {
            background: linear-gradient(135deg, #bde6f2 0%, #d4eef7 100%);
            color: #17607f;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(220, 245, 250, 0.6);
        }

        .actions a {
            text-decoration: none;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            margin-right: 6px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, transform 0.15s;
            display: inline-block;
        }

        .actions a.edit {
            background: #e5f4fa;
            color: #1e6b8c;
            border: 1px solid #b7dde8;
        }

        .actions a.edit:hover {
            background: #cbe9f4;
            transform: translateY(-1px);
        }

        .actions a.delete {
            background: #ffffff;
            color: #c94b5a;
            border: 1px solid #f0c8cd;
        }

        .actions a.delete:hover {
            background: #fbe7e9;
            transform: translateY(-1px);
        }

        .stats ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stats li {
            background: linear-gradient(135deg, #e6f7fb 0%, #d4f0ea 100%);
            border: 1px solid #b8e0e5;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.92rem;
            color: #1f6c85;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(120, 190, 210, 0.2);
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>Электронный журнал группы</h1>

        <div class="card">
            <h2>Добавить студента</h2>
            <form method="POST" action="index.php" class="add-form">
                <input type="text" name="name" placeholder="ФИО" required>
                <input type="email" name="email" placeholder="Email" required>
                <select name="group_id">
                    <option value="">— Выберите группу —</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_student">Добавить</button>
            </form>
        </div>

        <div class="card">
            <h2>Список студентов</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Группа</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= $student['id'] ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td><?= htmlspecialchars($student['group_name'] ?? '—') ?></td>
                        <td class="actions">
                            <a class="edit" href="edit.php?id=<?= $student['id'] ?>">Изменить</a>
                            <a class="delete" href="index.php?delete=<?= $student['id'] ?>"
                               onclick="return confirm('Удалить студента?');">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card stats">
            <h2>Статистика по группам</h2>
            <ul>
                <?php foreach ($stats as $stat): ?>
                    <li><?= htmlspecialchars($stat['name']) ?>: <?= $stat['count'] ?> студ.</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
require_once 'db.php';
$pdo = getPDO();

$studentId = (int)($_GET['id'] ?? 0);
if ($studentId === 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: index.php');
    exit;
}

$groups = $pdo->query("SELECT id, name FROM groups ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $groupId = $_POST['group_id'] ?: null;

    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, group_id = ? WHERE id = ?");
    $stmt->execute([$name, $email, $groupId, $studentId]);

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование студента</title>

    <!-- Красивый шрифт Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
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
            max-width: 640px;
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

        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(6px);
            border: 1px solid #bfe3ec;
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 6px 22px rgba(100, 180, 200, 0.2);
        }

        label {
            display: block;
            margin-bottom: 14px;
            color: #207b9d;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 600;
        }

        input, select {
            width: 100%;
            box-sizing: border-box;
            margin-top: 6px;
            padding: 10px 12px;
            border: 1px solid #b7dde8;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            color: #234;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus {
            border-color: #5cb8d8;
            box-shadow: 0 0 0 3px rgba(120, 200, 230, 0.4);
        }

        button {
            background: linear-gradient(135deg, #5ab4d8 0%, #3d94c9 100%);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
            box-shadow: 0 3px 10px rgba(80, 160, 200, 0.4);
        }

        button:hover {
            background: linear-gradient(135deg, #4aa5cc 0%, #2f80b5 100%);
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(80, 160, 200, 0.5);
        }

        .cancel {
            display: inline-block;
            margin-left: 12px;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            color: #1e6b8c;
            background: #ffffff;
            border: 1px solid #b7dde8;
            font-size: 0.95rem;
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            font-weight: 600;
            transition: background 0.2s, transform 0.15s;
        }

        .cancel:hover {
            background: #e6f7fb;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>Редактирование студента #<?= $student['id'] ?></h1>
        <div class="card">
            <form method="POST">
                <label>ФИО
                    <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
                </label>
                <label>Группа
                    <select name="group_id">
                        <option value="">— Не выбрана —</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"
                                <?= ($student['group_id'] == $group['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Сохранить</button>
                <a class="cancel" href="index.php">Отмена</a>
            </form>
        </div>
    </div>
</body>
</html>
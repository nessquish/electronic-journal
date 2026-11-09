<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';
// Подключаем класс пользователя
require_once 'classes/User.php';

// Вошедшему форма не нужна
if (current_user()) {
    redirect('index.php');
}

// Пока ошибки нет
$error = '';

// Форма отправлена
if (is_post()) {
    // Ищем пользователя по логину
    $user = User::findByLogin(trim($_POST['login'] ?? ''), $db);

    // Проверяем пароль
    if ($user && $user->verifyPassword($_POST['password'] ?? '')) {
        // Меняем номер сессии для защиты
        session_regenerate_id(true);
        // Запоминаем пользователя
        $_SESSION['user'] = $user->toSession();
        // Записываем вход
        add_log('Вход в систему');
        // Открываем главную
        redirect('index.php');
    }

    // Пароль или логин неверный
    $error = 'Неверный логин или пароль';
}

// Начинаем вывод страницы
page_header('Электронный журнал', 'Войдите, чтобы увидеть оценки и посещаемость');
?>
<?php show_error($error); ?>
<form method="post" class="form" style="max-width:none;display:flex;flex-direction:column;gap:14px">
    <p>Логин<br><input type="text" name="login"></p>
    <p>Пароль<br><input type="password" name="password"></p>
    <button type="submit">Войти</button>
</form>
<?php page_footer(); ?>

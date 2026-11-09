<?php
// Подключаем сессию, базу и общие функции
require 'auth.php';

// Записываем выход, если пользователь вошёл
if (current_user()) {
    add_log('Выход из системы');
}

// Очищаем данные сессии
$_SESSION = [];
// Удаляем Cookie сессии
setcookie(session_name(), '', time() - 3600, '/');
// Уничтожаем сессию
session_destroy();

// Возвращаемся ко входу
redirect('login.php');
